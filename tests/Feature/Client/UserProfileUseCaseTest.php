<?php

use App\Models\Booking;
use App\Models\Comment;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    config(['services.google_drive.enabled' => false]);
    Storage::fake('public');
});

function createProfileCustomer(array $attributes = []): User
{
    $user = User::factory()->create(array_merge([
        'name' => 'Nguyễn Văn Đạt',
        'email' => 'dat.nguyen_'.uniqid().'@ticketbox.test',
        'phone' => '0912345678',
        'password' => Hash::make('Password123!'),
        'is_active' => true,
    ], $attributes));

    $user->assignRole('user');

    return $user;
}

test('TC_UC020_001: authenticated user accesses GET /profile displaying accurate name, email, phone and avatar', function () {
    $user = createProfileCustomer([
        'name' => 'Trần Quang Khải',
        'phone' => '0987654321',
        'avatar' => 'avatars/khai_profile.png',
    ]);
    Storage::disk('public')->put('avatars/khai_profile.png', 'fake-image-bytes');

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk()
        ->assertSee('Trần Quang Khải')
        ->assertSee($user->email)
        ->assertSee('0987654321')
        ->assertSee($user->avatar_url);
});

test('TC_UC020_002: updates full name and valid phone number successfully with flash status in session', function () {
    $user = createProfileCustomer();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Trần Văn Cường',
        'email' => $user->email,
        'phone' => '0933112233',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'profile-updated');

    $user->refresh();
    expect($user->name)->toBe('Trần Văn Cường')
        ->and($user->phone)->toBe('0933112233');
});

test('TC_UC020_003: validation rejects empty name, names exceeding 255 chars, and invalid Vietnamese phone numbers', function () {
    $user = createProfileCustomer();

    // 1. Bỏ trống họ tên
    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => '',
        'email' => $user->email,
        'phone' => '0912345678',
    ])->assertSessionHasErrors('name');

    // 2. Họ tên vượt quá 255 ký tự
    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => str_repeat('A', 256),
        'email' => $user->email,
        'phone' => '0912345678',
    ])->assertSessionHasErrors('name');

    // 3. Số điện thoại chứa ký tự chữ
    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Trần Văn Hợp Lệ',
        'email' => $user->email,
        'phone' => '0912abc678',
    ])->assertSessionHasErrors('phone');

    // 4. Số điện thoại thiếu chữ số (12345)
    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Trần Văn Hợp Lệ',
        'email' => $user->email,
        'phone' => '12345',
    ])->assertSessionHasErrors('phone');

    // 5. Số điện thoại thừa chữ số (quá 11 số)
    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Trần Văn Hợp Lệ',
        'email' => $user->email,
        'phone' => '09123456789999',
    ])->assertSessionHasErrors('phone');
});

test('TC_UC020_004: XSS protection escapes malicious script tags in name field on profile and navigation', function () {
    $user = createProfileCustomer();
    $xssPayload = "<script>alert('XSS')</script>";

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $xssPayload,
        'email' => $user->email,
    ])->assertSessionHasNoErrors();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk()
        ->assertDontSee($xssPayload, false)
        ->assertSee('&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;', false);
});

test('TC_UC020_005: uploads valid avatar image file and updates user avatar column and public URL', function () {
    $user = createProfileCustomer();
    $avatarFile = UploadedFile::fake()->create('profile_pic.png', 400, 'image/png');

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $avatarFile,
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertSee($user->avatar_url);
});

test('TC_UC020_006: replacing avatar purges old image file from storage to prevent orphaned disk files', function () {
    $oldPath = 'avatars/old_legacy_avatar.png';
    Storage::disk('public')->put($oldPath, 'old-image-bytes');

    $user = createProfileCustomer(['avatar' => $oldPath]);
    Storage::disk('public')->assertExists($oldPath);

    $newAvatarFile = UploadedFile::fake()->create('new_avatar.jpg', 350, 'image/jpeg');

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $newAvatarFile,
    ])->assertSessionHasNoErrors();

    // Xác nhận file cũ đã bị xóa khỏi disk
    Storage::disk('public')->assertMissing($oldPath);

    // File mới được lưu
    $user->refresh();
    expect($user->avatar)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($user->avatar);
});

test('TC_UC020_007: avatar file size boundary validation passes at exactly 1024 KB and fails at 1025 KB', function () {
    $user = createProfileCustomer();

    // Mốc biên hợp lệ: đúng 1024 KB (1MB)
    $exact1024 = UploadedFile::fake()->create('exact1024.jpg', 1024, 'image/jpeg');
    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $exact1024,
    ])->assertSessionHasNoErrors();

    // Mốc biên vượt ngưỡng: 1025 KB (> 1MB)
    $overflow1025 = UploadedFile::fake()->create('overflow1025.jpg', 1025, 'image/jpeg');
    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $overflow1025,
    ])->assertSessionHasErrors('avatar');
});

test('TC_UC020_008: avatar format validation rejects disallowed formats (.pdf, .exe, .svg, .txt)', function () {
    $user = createProfileCustomer();

    $disallowedFiles = [
        UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('installer.exe', 100, 'application/x-msdownload'),
        UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml'),
        UploadedFile::fake()->create('script.txt', 10, 'text/plain'),
    ];

    foreach ($disallowedFiles as $file) {
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $file,
        ])->assertSessionHasErrors('avatar');
    }
});

test('TC_UC020_009: dedicated avatar upload request without attaching a file fails validation', function () {
    $user = createProfileCustomer();

    // Gửi request upload avatar chuyên biệt với avatar = null
    $this->actingAs($user)->post(route('profile.avatar.store'), [
        'avatar' => null,
    ])->assertSessionHasErrors('avatar');

    // Gửi request patch với cờ upload_avatar = true nhưng avatar = null
    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'upload_avatar' => true,
        'avatar' => null,
    ])->assertSessionHasErrors('avatar');
});

test('TC_UC020_010: password change succeeds with correct current password and valid new password (>= 8 chars)', function () {
    $user = createProfileCustomer(['password' => Hash::make('OldSecret123')]);

    $response = $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('password.update'), [
            'current_password' => 'OldSecret123',
            'password' => 'BrandNewSecret888',
            'password_confirmation' => 'BrandNewSecret888',
        ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect(Hash::check('BrandNewSecret888', $user->password))->toBeTrue()
        ->and(Hash::check('OldSecret123', $user->password))->toBeFalse();
});

test('TC_UC020_011: password change fails when incorrect current password is provided', function () {
    $user = createProfileCustomer(['password' => Hash::make('CorrectCurrent123')]);

    $response = $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('password.update'), [
            'current_password' => 'WrongCurrentPassword',
            'password' => 'BrandNewSecret888',
            'password_confirmation' => 'BrandNewSecret888',
        ]);

    $response->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect(route('profile.edit'));

    // Mật khẩu trong DB vẫn giữ nguyên giá trị cũ
    expect(Hash::check('CorrectCurrent123', $user->refresh()->password))->toBeTrue();
});

test('TC_UC020_012: new password length boundary validation fails at 7 chars and succeeds at 8 chars', function () {
    $user = createProfileCustomer(['password' => Hash::make('CurrentPass123')]);

    // Mốc biên dưới: 7 ký tự (length < 8) -> thất bại
    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('password.update'), [
            'current_password' => 'CurrentPass123',
            'password' => 'Pass123',
            'password_confirmation' => 'Pass123',
        ])
        ->assertSessionHasErrorsIn('updatePassword', 'password');

    // Mốc biên chuẩn: đúng 8 ký tự (length == 8) -> thành công
    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('password.update'), [
            'current_password' => 'CurrentPass123',
            'password' => 'Pass1234',
            'password_confirmation' => 'Pass1234',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('Pass1234', $user->refresh()->password))->toBeTrue();
});

test('TC_UC020_013: password confirmation validation fails when confirmation mismatches or fields are blank', function () {
    $user = createProfileCustomer(['password' => Hash::make('CurrentPass123')]);

    // 1. Không khớp xác nhận mật khẩu
    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('password.update'), [
            'current_password' => 'CurrentPass123',
            'password' => 'NewPassword888',
            'password_confirmation' => 'DifferentPassword999',
        ])
        ->assertSessionHasErrorsIn('updatePassword', 'password');

    // 2. Bỏ trống các trường
    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('password.update'), [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertSessionHasErrorsIn('updatePassword', ['current_password', 'password']);
});

test('TC_UC020_014: account deletion succeeds with correct password, terminates session and deletes user record', function () {
    $user = createProfileCustomer(['password' => Hash::make('AccountPassword123')]);

    $response = $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'AccountPassword123',
        ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect('/');

    expect(Auth::check())->toBeFalse()
        ->and(User::find($user->id))->toBeNull();
});

test('TC_UC020_015: account deletion fails with wrong password or empty password, retaining user session and record', function () {
    $user = createProfileCustomer(['password' => Hash::make('AccountPassword123')]);

    // 1. Nhập sai mật khẩu xác nhận
    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'IncorrectPassword',
        ])
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect(route('profile.edit'));

    expect(User::find($user->id))->not->toBeNull()
        ->and(Auth::check())->toBeTrue();

    // 2. Bỏ trống ô mật khẩu
    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => '',
        ])
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect(route('profile.edit'));

    expect(User::find($user->id))->not->toBeNull()
        ->and(Auth::check())->toBeTrue();
});

test('TC_UC020_016: relational integrity on account deletion cleanly handles cascade deletion of user bookings and comments without SQL 500', function () {
    $user = createProfileCustomer(['password' => Hash::make('CleanDelete123')]);

    $event = Event::factory()->create(['status' => 'published']);

    // Tạo booking và comment cho user
    $booking = Booking::create([
        'user_id' => $user->id,
        'total_price' => 200000,
        'status' => 'confirmed',
        'payment_method' => 'vietqr',
    ]);

    $comment = Comment::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'content' => 'Bình luận thử nghiệm trước khi xóa tài khoản',
        'rating' => 5,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'CleanDelete123',
        ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect('/');

    // Kiểm tra toàn vẹn DB
    expect(User::find($user->id))->toBeNull()
        ->and(Booking::find($booking->id))->toBeNull()
        ->and(Comment::find($comment->id))->toBeNull();
});

test('TC_UC020_017: unauthenticated guest accessing or modifying profile is intercepted by auth middleware', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->patch(route('profile.update'), ['name' => 'Guest'])->assertRedirect(route('login'));
    $this->post(route('profile.avatar.store'), [])->assertRedirect(route('login'));
    $this->put(route('password.update'), [])->assertRedirect(route('login'));
    $this->delete(route('profile.destroy'), [])->assertRedirect(route('login'));
});

test('TC_UC020_018: locked user with is_active = false is intercepted by EnsureUserIsActive middleware and logged out', function () {
    $lockedUser = createProfileCustomer(['is_active' => false]);

    $this->actingAs($lockedUser)
        ->get(route('profile.edit'))
        ->assertRedirect(route('login'));

    expect(Auth::check())->toBeFalse();
});
