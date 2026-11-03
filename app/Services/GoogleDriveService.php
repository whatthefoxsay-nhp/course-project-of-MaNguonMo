<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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
            && ($this->hasOAuthCredentials() || is_file($this->credentialPath()));
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
                'fields' => 'id',
            ]);

            $fileId = (string) $uploaded->getId();
            if ($fileId === '') {
                return null;
            }

            $drive->permissions->create($fileId, new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]), ['supportsAllDrives' => true]);

            return "https://drive.google.com/uc?export=view&id={$fileId}";
        } catch (Throwable $exception) {
            if ($fileId !== null && $fileId !== '') {
                try {
                    $this->drive()->files->delete($fileId, ['supportsAllDrives' => true]);
                } catch (Throwable) {
                    // Keep the original upload error as the fallback reason.
                }
            }

            Log::warning('Google Drive image upload failed; the image upload will be rejected.', [
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

    /** @return array{content: string, mime_type: string}|null */
    public function downloadImage(string $fileId): ?array
    {
        if (! $this->isEnabled() || ! preg_match('/^[A-Za-z0-9_-]+$/', $fileId)) {
            return null;
        }

        try {
            $response = $this->drive()->files->get($fileId, [
                'alt' => 'media',
                'supportsAllDrives' => true,
            ]);
            $mimeType = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));

            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'], true)) {
                return null;
            }

            return [
                'content' => $response->getBody()->getContents(),
                'mime_type' => $mimeType,
            ];
        } catch (Throwable $exception) {
            Log::warning('Google Drive image download failed.', [
                'exception' => $exception::class,
            ]);

            return null;
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
        if ($this->hasOAuthCredentials()) {
            $client->setClientId((string) config('services.google_drive.client_id'));
            $client->setClientSecret((string) config('services.google_drive.client_secret'));
            $client->setAccessType('offline');
            // This app writes into a user-selected existing My Drive folder.
            $client->addScope(Drive::DRIVE);
            $token = $client->fetchAccessTokenWithRefreshToken(
                (string) config('services.google_drive.refresh_token')
            );
            if (isset($token['error'])) {
                throw new RuntimeException('Google Drive OAuth token refresh failed.');
            }
            $client->setAccessToken($token);
        } else {
            $client->setAuthConfig($this->credentialPath());
            $client->addScope(Drive::DRIVE_FILE);
        }

        return $this->drive = new Drive($client);
    }

    private function hasOAuthCredentials(): bool
    {
        return trim((string) config('services.google_drive.client_id')) !== ''
            && trim((string) config('services.google_drive.client_secret')) !== ''
            && trim((string) config('services.google_drive.refresh_token')) !== '';
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
