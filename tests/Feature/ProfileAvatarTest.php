<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function profilePayload(User $user, array $overrides = []): array
{
    return array_merge(['name' => $user->name, 'email' => $user->email], $overrides);
}

test('a user uploads an avatar and sets a phone number', function () {
    $user = createCustomer();

    $this->actingAs($user)
        ->patch(route('profile.update'), profilePayload($user, [
            'phone' => '0912 345 678',
            'avatar' => UploadedFile::fake()->image('me.png', 200, 200),
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->phone)->toBe('0912 345 678')
        ->and($user->avatar)->toStartWith('avatars/')
        ->and($user->avatar_url)->toContain('/storage/avatars/');
    Storage::disk('public')->assertExists($user->avatar);
});

test('uploading a new avatar deletes the old file', function () {
    Storage::disk('public')->put('avatars/old.png', 'x');
    $user = createCustomer(['avatar' => 'avatars/old.png']);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->image('new.webp'),
    ]))->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing('avatars/old.png');
    Storage::disk('public')->assertExists($user->fresh()->avatar);
});

test('saving the profile without a file keeps the current avatar', function () {
    Storage::disk('public')->put('avatars/keep.png', 'x');
    $user = createCustomer(['avatar' => 'avatars/keep.png']);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, ['name' => 'Tên mới']));

    expect($user->fresh()->avatar)->toBe('avatars/keep.png');
    Storage::disk('public')->assertExists('avatars/keep.png');
});

test('avatar must be an image of at most 2MB and phone must look like a phone number', function () {
    $user = createCustomer();

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
        'phone' => 'gọi tôi nhé',
    ]))->assertSessionHasErrors(['avatar', 'phone']);

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->image('big.jpg')->size(2049),
    ]))->assertSessionHasErrors('avatar');

    $this->actingAs($user)->patch(route('profile.update'), profilePayload($user, [
        'avatar' => UploadedFile::fake()->image('ok.jpg')->size(2048),
    ]))->assertSessionHasNoErrors();
});

test('initials fall back correctly for vietnamese names', function () {
    expect(User::factory()->make(['name' => 'Đỗ Ánh'])->initials)->toBe('ĐÁ')
        ->and(User::factory()->make(['name' => 'ánh'])->initials)->toBe('Á')
        ->and(User::factory()->make(['name' => '  '])->initials)->toBe('?')
        ->and(User::factory()->make(['avatar' => null])->avatar_url)->toBeNull()
        ->and(User::factory()->make(['avatar' => 'https://example.com/a.png'])->avatar_url)->toBe('https://example.com/a.png');
});

test('the header and profile page show the avatar or initials', function () {
    $user = createCustomer(['name' => 'Đặng Thu']);

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('ĐT')
        ->assertSee('enctype="multipart/form-data"', false);

    $user->forceFill(['avatar' => 'avatars/me.png'])->save();

    $this->actingAs($user->fresh())->get(route('home'))
        ->assertOk()
        ->assertSee(Storage::disk('public')->url('avatars/me.png'), false);
});
