<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('admin can view user management page with correct layout', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertSee('Quản Lý Tài Khoản Khách Hàng & Quản Trị');
});

test('admin can filter users by search query, role and status', function () {
    $admin = User::factory()->create(['name' => 'Admin Boss', 'email' => 'boss@ticketbox.vn']);
    $admin->assignRole('admin');

    $activeUser = User::factory()->create(['name' => 'Nguyen Van An', 'email' => 'an@gmail.com', 'is_active' => true]);
    $activeUser->assignRole('user');

    $lockedUser = User::factory()->create(['name' => 'Tran Van Khoa', 'email' => 'khoa@gmail.com', 'is_active' => false]);
    $lockedUser->assignRole('user');

    // Search by query
    $response = $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'Nguyen']));
    $response->assertOk();
    $response->assertSee('Nguyen Van An');
    $response->assertDontSee('Tran Van Khoa');

    // Filter by locked status
    $responseLocked = $this->actingAs($admin)->get(route('admin.users.index', ['status' => 'locked']));
    $responseLocked->assertOk();
    $responseLocked->assertSee('Tran Van Khoa');
});

test('admin can toggle user lock status via ajax', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create(['name' => 'Target User', 'is_active' => true]);
    $targetUser->assignRole('user');

    $response = $this->actingAs($admin)->patchJson(route('admin.users.toggle-status', $targetUser));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);

    expect($targetUser->fresh()->is_active)->toBeFalse();

    // Toggle back to active
    $responseUnlock = $this->actingAs($admin)->patchJson(route('admin.users.toggle-status', $targetUser));
    $responseUnlock->assertOk();
    expect($targetUser->fresh()->is_active)->toBeTrue();
});

test('admin cannot lock their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->patchJson(route('admin.users.toggle-status', $admin));

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
});

test('admin can toggle role between user and admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create();
    $targetUser->assignRole('user');

    $this->actingAs($admin)->patch(route('admin.users.toggle-role', $targetUser))
        ->assertRedirect();
    expect($targetUser->fresh()->hasRole('admin'))->toBeTrue();

    // Toggle back
    $this->actingAs($admin)->patch(route('admin.users.toggle-role', $targetUser))
        ->assertRedirect();
    expect($targetUser->fresh()->hasRole('user'))->toBeTrue();
});

test('admin cannot toggle their own role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->patch(route('admin.users.toggle-role', $admin))
        ->assertRedirect();
    expect($admin->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin can reset password of a user to default temporary password', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $targetUser = User::factory()->create(['password' => 'old_secret_pwd']);

    $response = $this->actingAs($admin)->post(route('admin.users.reset-password', $targetUser));

    $response->assertRedirect();
    $tempPassword = 'TicketBox@' . date('Y');
    expect(Illuminate\Support\Facades\Hash::check($tempPassword, $targetUser->fresh()->password))->toBeTrue();
});

test('admin can create a new user account with specified role and status', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Nguyen Staff',
        'email' => 'staff@ticketbox.vn',
        'phone' => '0987654321',
        'password' => 'StaffPassword123',
        'role' => 'admin',
        'is_active' => '1',
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');

    $newUser = User::where('email', 'staff@ticketbox.vn')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->name)->toBe('Nguyen Staff')
        ->and($newUser->phone)->toBe('0987654321')
        ->and($newUser->is_active)->toBeTrue()
        ->and($newUser->hasRole('admin'))->toBeTrue();
});

test('admin cannot create a user with duplicate email', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    User::factory()->create(['email' => 'existing@ticketbox.vn']);

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Duplicate Person',
        'email' => 'existing@ticketbox.vn',
        'password' => 'ValidPassword123',
        'role' => 'user',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('regular user cannot create user accounts via admin endpoint', function () {
    $regularUser = User::factory()->create();
    $regularUser->assignRole('user');

    $response = $this->actingAs($regularUser)->post(route('admin.users.store'), [
        'name' => 'Hacker Admin',
        'email' => 'hacker@test.vn',
        'password' => 'Hacker12345',
        'role' => 'admin',
    ]);

    $response->assertForbidden();
});

describe('UC_001: Thêm người dùng mới validation', function () {
    test('TC_UC001_16: Thêm người dùng mới với mật khẩu không hợp lệ - mật khẩu không đủ 8 ký tự', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Nguyen Van Test',
            'email' => 'shortpwd@ticketbox.vn',
            'phone' => '0987654321',
            'password' => '1234567', // chỉ 7 ký tự (< 8)
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'Mật khẩu không hợp lệ',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'shortpwd@ticketbox.vn',
        ]);
    });

    test('TC_UC001_17: Thêm người dùng mới với số điện thoại không hợp lệ - bắt đầu không phải số 0', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Nguyen Van Test Phone',
            'email' => 'phoneinvalid1@ticketbox.vn',
            'phone' => '1987654321', // Bắt đầu bằng 1 thay vì 0
            'password' => 'ValidPassword123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors([
            'phone' => 'SĐT không đúng định dạng hoặc đủ 10 chữ số',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'phoneinvalid1@ticketbox.vn',
        ]);
    });

    test('TC_UC001_18: Thêm người dùng mới với số điện thoại không hợp lệ - không đúng 10 chữ số (9 số hoặc 11 số)', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Ca 1: 9 chữ số
        $response1 = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Nguyen 9 Digits',
            'email' => 'phone9digits@ticketbox.vn',
            'phone' => '098765432', // 9 số
            'password' => 'ValidPassword123',
            'role' => 'user',
        ]);

        $response1->assertSessionHasErrors([
            'phone' => 'SĐT không đúng định dạng hoặc đủ 10 chữ số',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'phone9digits@ticketbox.vn',
        ]);

        // Ca 2: 11 chữ số
        $response2 = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Nguyen 11 Digits',
            'email' => 'phone11digits@ticketbox.vn',
            'phone' => '09876543210', // 11 số
            'password' => 'ValidPassword123',
            'role' => 'user',
        ]);

        $response2->assertSessionHasErrors([
            'phone' => 'SĐT không đúng định dạng hoặc đủ 10 chữ số',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'phone11digits@ticketbox.vn',
        ]);
    });

    test('TC_UC001_19: Thêm mới người dùng với email đã tồn tại trong database', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Tạo sẵn 1 người dùng đã có trong database
        User::factory()->create([
            'email' => 'existing_user@ticketbox.vn',
        ]);

        $userCountBefore = User::count();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Duplicate Email User',
            'email' => 'existing_user@ticketbox.vn',
            'phone' => '0912345678',
            'password' => 'ValidPassword123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email đã tồn tại trong hệ thống.',
        ]);

        // Đảm bảo không có tài khoản trùng lặp nào được thêm vào DB
        expect(User::count())->toBe($userCountBefore);
    });

    test('TC_UC001_20: Mở khoá tài khoản người dùng khác đang bị khoá qua AJAX', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $lockedUser = User::factory()->create([
            'name' => 'Tran Thi Khoa',
            'email' => 'locked_user@ticketbox.vn',
            'is_active' => false,
        ]);
        $lockedUser->assignRole('user');

        // Gửi request mở khóa qua AJAX
        $response = $this->actingAs($admin)->patchJson(route('admin.users.toggle-status', $lockedUser));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "Đã mở khóa tài khoản {$lockedUser->name} thành công.",
            'user' => [
                'id' => $lockedUser->id,
                'name' => $lockedUser->name,
                'is_active' => true,
                'status_label' => 'Đang hoạt động',
            ],
        ]);

        // Cập nhật Database thành công
        $this->assertDatabaseHas('users', [
            'id' => $lockedUser->id,
            'is_active' => true,
        ]);
        expect($lockedUser->fresh()->is_active)->toBeTrue();

        // Kiểm tra trên giao diện khi tải lại/xem trang: nút đã chuyển sang trạng thái "Khóa"
        $pageResponse = $this->actingAs($admin)->get(route('admin.users.index'));
        $pageResponse->assertOk();
        $pageResponse->assertSee('Tran Thi Khoa');
        $pageResponse->assertSee('Hoạt động');
        $pageResponse->assertSee('Khóa');
    });

    test('TC_UC001_21: Khóa tài khoản người dùng khác đang hoạt động qua AJAX', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $activeUser = User::factory()->create([
            'name' => 'Le Van Active',
            'email' => 'active_user@ticketbox.vn',
            'is_active' => true,
        ]);
        $activeUser->assignRole('user');

        // Gửi request khóa qua AJAX
        $response = $this->actingAs($admin)->patchJson(route('admin.users.toggle-status', $activeUser));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => "Đã khóa tài khoản {$activeUser->name} thành công.",
            'user' => [
                'id' => $activeUser->id,
                'name' => $activeUser->name,
                'is_active' => false,
                'status_label' => 'Đã bị khóa',
            ],
        ]);

        // Cập nhật Database thành công
        $this->assertDatabaseHas('users', [
            'id' => $activeUser->id,
            'is_active' => false,
        ]);
        expect($activeUser->fresh()->is_active)->toBeFalse();
    });
});


