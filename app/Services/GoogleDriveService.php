<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleDriveService
{
    private ?Drive $drive = null;

    /** @var array<string, string> */
    private array $folderIds = [];

    public function __construct(private readonly ?Client $client = null, ?Drive $drive = null)
    {
        $this->drive = $drive;
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.google_drive.enabled')
            && trim((string) config('services.google_drive.folder_id')) !== ''
            && is_file($this->credentialPath());
    }

    public function upload(UploadedFile $file, string $subfolder = 'posters'): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $fileId = null;

        try {
            $drive = $this->drive();
            $parents = [trim((string) config('services.google_drive.folder_id'))];

            if ($subfolder !== '') {
                $parents = [$this->folderId($drive, $parents[0], $subfolder)];
            }

            $uploaded = $drive->files->create(new DriveFile([
                'name' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'parents' => $parents,
            ]), [
                'data' => file_get_contents($file->getRealPath()),
                'mimeType' => $file->getMimeType(),
                'uploadType' => 'multipart',
                'supportsAllDrives' => true,
                'fields' => 'id,webViewLink,webContentLink',
            ]);

            $fileId = (string) $uploaded->getId();
            if ($fileId === '') {
                return null;
            }

            $drive->permissions->create($fileId, new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]), ['supportsAllDrives' => true]);

            return $uploaded->getWebContentLink()
                ?: "https://drive.google.com/uc?export=view&id={$fileId}";
        } catch (Throwable $exception) {
            if ($fileId !== null && $fileId !== '') {
                try {
                    $this->drive()->files->delete($fileId, ['supportsAllDrives' => true]);
                } catch (Throwable) {
                    // Keep the original upload error as the fallback reason.
                }
            }

            Log::warning('Google Drive image upload failed; local storage fallback will be used.', [
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    public function delete(string $fileId): bool
    {
        if (! $this->isEnabled() || trim($fileId) === '') {
            return false;
        }

        try {
            $this->drive()->files->delete($fileId, ['supportsAllDrives' => true]);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Google Drive image deletion failed.', [
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    public function extractFileId(string $url): ?string
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($host, ['drive.google.com', 'docs.google.com'], true)) {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        if (isset($query['id']) && is_string($query['id']) && preg_match('/^[A-Za-z0-9_-]+$/', $query['id'])) {
            return $query['id'];
        }

        if (preg_match('~/(?:file/)?d/([A-Za-z0-9_-]+)(?:/|$)~', (string) ($parts['path'] ?? ''), $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function drive(): Drive
    {
        if ($this->drive) {
            return $this->drive;
        }

        $client = $this->client ?? new Client;
        $client->setAuthConfig($this->credentialPath());
        $client->addScope(Drive::DRIVE_FILE);

        return $this->drive = new Drive($client);
    }

    /** @param list<string> $parents */
    private function folderId(Drive $drive, string $parentId, string $name): string
    {
        $key = $parentId.':'.$name;
        if (isset($this->folderIds[$key])) {
            return $this->folderIds[$key];
        }

        $escapedName = str_replace("'", "\\'", $name);
        $folders = $drive->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.folder' and name='{$escapedName}' and '{$parentId}' in parents and trashed=false",
            'pageSize' => 1,
            'fields' => 'files(id,name)',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ])->getFiles();

        if ($folders !== []) {
            return $this->folderIds[$key] = (string) $folders[0]->getId();
        }

        $folder = $drive->files->create(new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]), ['fields' => 'id', 'supportsAllDrives' => true]);

        return $this->folderIds[$key] = (string) $folder->getId();
    }

    private function credentialPath(): string
    {
        $path = trim((string) config('services.google_drive.service_account'));

        return preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/])/', $path)
            ? $path
            : base_path($path);
    }
}
