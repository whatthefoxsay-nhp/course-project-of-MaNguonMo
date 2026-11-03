<?php

use App\Services\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function profilePayload($user, array $overrides = []): array
{
    return array_merge(['name' => $user->name, 'email' => $user->email], $overrides);
}

test('a user uploads an avatar and sets a phone number', function () {
    $user = createCustomer();
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('isEnabled')->once()->andReturn(true);
    $drive->shouldReceive('upload')->once()->with(Mockery::type(UploadedFile::class), 'avatars')
        ->andReturn('https://drive.google.com/uc?export=view&id=user_avatar_123');
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, [
            'phone' => '0912 345 678',
            'avatar' => UploadedFile::fake()->create('me.png', 10, 'image/png'),
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->phone)->toBe('0912 345 678')
        ->and($user->avatar)->toBe('https://drive.google.com/uc?export=view&id=user_avatar_123')
        ->and($user->avatar_url)->toBe(route('users.avatar', $user).'?v=user_avatar_123');
});

test('uploading a new avatar deletes the old file', function () {
    Storage::disk('public')->put('avatars/old.png', 'x');
    $user = createCustomer(['avatar' => 'avatars/old.png']);
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('isEnabled')->once()->andReturn(true);
    $drive->shouldReceive('upload')->once()->with(Mockery::type(UploadedFile::class), 'avatars')
        ->andReturn('https://drive.google.com/uc?export=view&id=user_avatar_new');
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->create('new.png', 10, 'image/png'),
    ]));

    Storage::disk('public')->assertMissing('avatars/old.png');
});

test('saving the profile without a file keeps the current avatar', function () {
    Storage::disk('public')->put('avatars/keep.png', 'x');
    $user = createCustomer(['avatar' => 'avatars/keep.png']);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, ['name' => 'Tên mới']));

    expect($user->fresh()->avatar)->toBe('avatars/keep.png');
    Storage::disk('public')->assertExists('avatars/keep.png');
});

test('avatar must be a small image and phone must look like a phone number', function () {
    $user = createCustomer();

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
        'phone' => 'gọi tôi nhé',
    ]))->assertSessionHasErrors(['avatar', 'phone']);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->create('big.jpg', 2048, 'image/jpeg'),
    ]))->assertSessionHasErrors('avatar');
});

test('public avatar route streams image bytes from Drive through the app', function () {
    $user = createCustomer(['avatar' => 'https://drive.google.com/uc?id=user_avatar_123&export=download']);
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('extractFileId')->once()
        ->with('https://drive.google.com/uc?id=user_avatar_123&export=download')
        ->andReturn('user_avatar_123');
    $drive->shouldReceive('downloadImage')->once()->with('user_avatar_123')
        ->andReturn(['content' => 'avatar bytes', 'mime_type' => 'image/png']);
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->get(route('users.avatar', $user))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertContent('avatar bytes');
});

test('the profile page shows the avatar upload field', function () {
    $this->actingAs(createCustomer())->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('name="avatar"', false)
        ->assertSee('enctype="multipart/form-data"', false);
});

test('initials fall back correctly for vietnamese names', function () {
    expect(App\Models\User::factory()->make(['name' => 'Đỗ Ánh'])->initials)->toBe('ĐÁ')
        ->and(App\Models\User::factory()->make(['name' => 'ánh'])->initials)->toBe('Á')
        ->and(App\Models\User::factory()->make(['name' => '  '])->initials)->toBe('?')
        ->and(App\Models\User::factory()->make(['avatar' => null])->avatar_url)->toBeNull()
        ->and(App\Models\User::factory()->make(['avatar' => 'https://example.com/a.png'])->avatar_url)->toBe('https://example.com/a.png');
});
