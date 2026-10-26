<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function profilePayload($user, array $overrides = []): array
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
        'avatar' => UploadedFile::fake()->image('new.png'),
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
        'avatar' => UploadedFile::fake()->image('big.jpg')->size(2048),
    ]))->assertSessionHasErrors('avatar');
});

test('the profile page shows the avatar upload field', function () {
    $this->actingAs(createCustomer())->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('name="avatar"', false)
        ->assertSee('enctype="multipart/form-data"', false);
});
