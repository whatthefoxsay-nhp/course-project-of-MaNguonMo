<?php

use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Google\Service\Drive\Resource\Files;
use Google\Service\Drive\Resource\Permissions;
use Illuminate\Http\UploadedFile;

function driveCredentialFile(): string
{
    $path = tempnam(sys_get_temp_dir(), 'drive-service-account-');
    file_put_contents($path, '{"type":"service_account"}');

    return $path;
}

afterEach(function () {
    $path = config('services.google_drive.service_account');
    if (is_string($path) && str_contains($path, 'drive-service-account-') && is_file($path)) {
        unlink($path);
    }
});

it('reports disabled when Drive is not enabled or credentials are missing', function () {
    config([
        'services.google_drive.enabled' => false,
        'services.google_drive.folder_id' => 'folder-123',
        'services.google_drive.service_account' => 'missing-service-account.json',
        'services.google_drive.client_id' => '',
        'services.google_drive.client_secret' => '',
        'services.google_drive.refresh_token' => '',
    ]);

    expect(app(GoogleDriveService::class)->isEnabled())->toBeFalse();

    config([
        'services.google_drive.enabled' => true,
        'services.google_drive.service_account' => 'missing-service-account.json',
    ]);

    expect(app(GoogleDriveService::class)->isEnabled())->toBeFalse();
});

it('enables Drive with personal-account OAuth credentials without a service-account file', function () {
    config([
        'services.google_drive.enabled' => true,
        'services.google_drive.folder_id' => 'folder-123',
        'services.google_drive.service_account' => 'missing-service-account.json',
        'services.google_drive.client_id' => 'client-id',
        'services.google_drive.client_secret' => 'client-secret',
        'services.google_drive.refresh_token' => 'refresh-token',
    ]);

    expect(app(GoogleDriveService::class)->isEnabled())->toBeTrue();
});

it('uploads an image, makes it public, and returns a direct view URL', function () {
    $credential = driveCredentialFile();
    config([
        'services.google_drive.enabled' => true,
        'services.google_drive.folder_id' => 'folder-123',
        'services.google_drive.service_account' => $credential,
    ]);

    $fileResource = Mockery::mock(Files::class);
    $permissionResource = Mockery::mock(Permissions::class);
    $drive = Mockery::mock(Drive::class);
    $uploaded = Mockery::mock(DriveFile::class);

    $drive->files = $fileResource;
    $drive->permissions = $permissionResource;
    $uploaded->shouldReceive('getId')->once()->andReturn('file_123');

    $fileResource->shouldReceive('listFiles')->once()->andReturn(new class
    {
        public function getFiles(): array
        {
            return [];
        }
    });
    $fileResource->shouldReceive('create')->twice()->andReturnUsing(function ($metadata, $options = []) use ($uploaded) {
        if ($metadata->getMimeType() === 'application/vnd.google-apps.folder') {
            return new class
            {
                public function getId(): string
                {
                    return 'posters-folder';
                }
            };
        }

        expect($metadata)->toBeInstanceOf(DriveFile::class)
            ->and($metadata->getParents())->toBe(['posters-folder'])
            ->and($options['uploadType'])->toBe('multipart');

        return $uploaded;
    });
    $permissionResource->shouldReceive('create')->once()->with('file_123', Mockery::type(Permission::class), ['supportsAllDrives' => true]);

    $service = new GoogleDriveService(drive: $drive);
    $url = $service->upload(UploadedFile::fake()->create('poster.png', 1, 'image/png'));

    expect($url)->toBe('https://drive.google.com/uc?export=view&id=file_123');
});

it('returns null for local fallback when Drive is disabled', function () {
    config([
        'services.google_drive.enabled' => false,
        'services.google_drive.folder_id' => 'folder-123',
        'services.google_drive.service_account' => 'missing-service-account.json',
    ]);

    expect(app(GoogleDriveService::class)->upload(UploadedFile::fake()->create('poster.png', 1, 'image/png')))->toBeNull();
});

it('downloads image bytes from Drive for same-origin image responses', function () {
    $credential = driveCredentialFile();
    config([
        'services.google_drive.enabled' => true,
        'services.google_drive.folder_id' => 'folder-123',
        'services.google_drive.service_account' => $credential,
        'services.google_drive.client_id' => '',
        'services.google_drive.client_secret' => '',
        'services.google_drive.refresh_token' => '',
    ]);

    $fileResource = Mockery::mock(Files::class);
    $drive = Mockery::mock(Drive::class);
    $drive->files = $fileResource;
    $fileResource->shouldReceive('get')
        ->once()
        ->with('file_123', ['alt' => 'media', 'supportsAllDrives' => true])
        ->andReturn(new GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'image/jpeg'], 'image bytes'));

    $image = (new GoogleDriveService(drive: $drive))->downloadImage('file_123');

    expect($image)->toBe(['content' => 'image bytes', 'mime_type' => 'image/jpeg']);
});

it('extracts file IDs only from supported Google Drive URLs', function () {
    $service = app(GoogleDriveService::class);

    expect($service->extractFileId('https://drive.google.com/file/d/file_123/view?usp=sharing'))
        ->toBe('file_123')
        ->and($service->extractFileId('https://drive.google.com/uc?export=view&id=file_456'))
        ->toBe('file_456')
        ->and($service->extractFileId('https://example.com/file/d/file_123/view'))
        ->toBeNull();
});
