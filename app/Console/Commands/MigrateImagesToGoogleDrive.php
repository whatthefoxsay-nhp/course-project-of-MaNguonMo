<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateImagesToGoogleDrive extends Command
{
    protected $signature = 'images:migrate-to-drive {--dry-run : Count eligible images without uploading or changing records}';

    protected $description = 'Move event posters and user avatars to Google Drive while retaining local originals';

    private int $migrated = 0;

    private int $alreadyOnDrive = 0;

    private int $failed = 0;

    public function handle(GoogleDriveService $drive): int
    {
        if (! $drive->isEnabled()) {
            $this->error('Google Drive is not enabled or its credentials/folder are missing.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->migrateTable('events', 'poster_path', 'posters', 'event poster', $drive, $dryRun);
        $this->migrateTable('users', 'avatar', 'avatars', 'user avatar', $drive, $dryRun);

        $this->newLine();
        $this->info(($dryRun ? 'Dry run' : 'Migration').' summary: '.$this->migrated.' migrated, '.$this->alreadyOnDrive.' already on Drive, '.$this->failed.' failed.');

        return $this->failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function migrateTable(string $table, string $column, string $subfolder, string $label, GoogleDriveService $drive, bool $dryRun): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->orderBy('id')
            ->chunk(100, function ($records) use ($table, $column, $subfolder, $label, $drive, $dryRun): void {
                foreach ($records as $record) {
                    $source = (string) $record->{$column};
                    $fileId = $drive->extractFileId($source);

                    if ($fileId !== null) {
                        if ($dryRun) {
                            $this->alreadyOnDrive++;
                            continue;
                        }

                        if ($drive->downloadImage($fileId) !== null) {
                            $this->alreadyOnDrive++;
                        } else {
                            $this->failed++;
                            $this->warn("{$label} #{$record->id}: existing Drive image could not be read.");
                        }

                        continue;
                    }

                    if ($dryRun) {
                        $this->line("Would migrate {$label} #{$record->id}.");
                        $this->migrated++;
                        continue;
                    }

                    try {
                        [$temporaryPath, $filename, $mimeType] = $this->sourceFile($source, $record->id, $label);
                        try {
                            $uploadedFile = new UploadedFile($temporaryPath, $filename, $mimeType, null, true);
                            $driveUrl = $drive->upload($uploadedFile, $subfolder);
                        } finally {
                            if (is_file($temporaryPath)) {
                                unlink($temporaryPath);
                            }
                        }

                        $newFileId = $driveUrl === null ? null : $drive->extractFileId($driveUrl);
                        if ($driveUrl === null || $newFileId === null) {
                            throw new \RuntimeException('Drive upload failed.');
                        }

                        $downloaded = $drive->downloadImage($newFileId);
                        if ($downloaded === null) {
                            $drive->delete($newFileId);
                            throw new \RuntimeException('Uploaded image could not be read back from Drive.');
                        }

                        $updated = DB::table($table)
                            ->where('id', $record->id)
                            ->where($column, $source)
                            ->update([$column => $driveUrl, 'updated_at' => now()]);

                        if ($updated !== 1) {
                            $drive->delete($newFileId);
                            throw new \RuntimeException('Record changed during migration; database was left untouched.');
                        }

                        $this->migrated++;
                        $this->info("Migrated {$label} #{$record->id}.");
                    } catch (Throwable $exception) {
                        $this->failed++;
                        $this->error("{$label} #{$record->id}: {$exception->getMessage()}");
                    }
                }
            });
    }

    /** @return array{string, string, string} temporary path, upload filename, MIME type */
    private function sourceFile(string $source, int $recordId, string $label): array
    {
        if (preg_match('~^https?://~i', $source)) {
            $host = strtolower((string) parse_url($source, PHP_URL_HOST));
            if ($host !== 'picsum.photos') {
                throw new \RuntimeException("Remote image host '{$host}' is not approved for migration.");
            }

            $response = Http::timeout(30)->get($source);
            if (! $response->successful()) {
                throw new \RuntimeException('Remote image download returned HTTP '.$response->status().'.');
            }

            $content = $response->body();
            $mimeType = strtolower(trim(explode(';', $response->header('Content-Type', ''))[0]));
        } else {
            if (! Storage::disk('public')->exists($source)) {
                throw new \RuntimeException('Local image file is missing.');
            }

            $absolutePath = Storage::disk('public')->path($source);
            $content = Storage::disk('public')->get($source);
            $mimeType = mime_content_type($absolutePath) ?: '';
        }

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'], true)) {
            throw new \RuntimeException("Unsupported image MIME type '{$mimeType}'.");
        }

        if ($content === '' || strlen($content) > 25 * 1024 * 1024) {
            throw new \RuntimeException('Image is empty or exceeds the 25 MB migration limit.');
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
        };
        $temporaryPath = tempnam(sys_get_temp_dir(), 'drive-image-migration-');
        if ($temporaryPath === false || file_put_contents($temporaryPath, $content) === false) {
            throw new \RuntimeException('Could not prepare a temporary image for upload.');
        }

        return [$temporaryPath, str_replace(' ', '-', $label).'-'.$recordId.'.'.$extension, $mimeType];
    }
}
