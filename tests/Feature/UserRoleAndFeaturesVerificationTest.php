<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\RoomSeatGenerator;
use App\Services\SeatHoldService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->category = Category::create([
        'name' => 'Concert',
        'slug' => 'concert',
        'description' => 'Đại nhạc hội',
    ]);

    $this->event = Event::create([
        'category_id' => $this->category->id,
        'title' => 'Live Concert Đỉnh Cao 2026',
        'slug' => 'live-concert-dinh-cao-2026',
        'description' => 'Mô tả concert siêu hoành tráng.',
        'duration_minutes' => 180,
        'release_date' => now()->toDateString(),
        'status' => 'published',
        'is_seated' => true,
        'base_price' => 250000,
    ]);

    $this->room = Room::create([
        'name' => 'Khán phòng Sân Vận Động',
        'address' => 'TP. Hồ Chí Minh',
        'capacity' => 100,
        'layout_preset' => 'custom_grid',
        'seat_config' => ['rows' => 3, 'cols' => 4],
    ]);
    app(RoomSeatGenerator::class)->generate($this->room, 'custom_grid', ['rows' => 3, 'cols' => 4]);

    $this->showtime = Showtime::create([
        'event_id' => $this->event->id,
        'room_id' => $this->room->id,
        'start_time' => now()->addDays(5)->setTime(19, 0),
        'end_time' => now()->addDays(5)->setTime(22, 0),
        'base_price' => 250000,
    ]);
    app(\App\Services\ShowtimeSeatGenerator::class)->generate($this->showtime);

    $this->user = User::factory()->create([
        'name' => 'Nguyễn Khách Hàng',
        'email' => 'khachhang@ticketbox.vn',
        'phone' => '0912345678',
        'password' => 'UserSecret123',
        'is_active' => true,
    ]);
    $this->user->assignRole('user');
});

/*
|--------------------------------------------------------------------------
| 1. Đăng ký & Xác thực Tài khoản Người dùng (Registration & Auth)
|--------------------------------------------------------------------------
*/
describe('User Registration, Authentication & Role Assignment', function () {
    test('guest can register account and is automatically assigned user role with active status', function () {
        $response = $this->post(route('register'), [
            'name' => 'Trần Văn Mới',
            'email' => 'tranvanmoi@ticketbox.vn',
            'phone' => '0987654321',
            'password' => 'SecretPassword123',
            'password_confirmation' => 'SecretPassword123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $newUser = User::where('email', 'tranvanmoi@ticketbox.vn')->first();
        expect($newUser)->not->toBeNull()
            ->and($newUser->hasRole('user'))->toBeTrue()
            ->and($newUser->hasRole('admin'))->toBeFalse()
            ->and($newUser->is_active)->toBeTrue()
            ->and($newUser->phone)->toBe('0987654321');
    });

    test('registration rejects duplicate email or mismatched password', function () {
        // Duplicate email
        $resDup = $this->post(route('register'), [
            'name' => 'Trùng Email',
            'email' => 'khachhang@ticketbox.vn',
            'password' => 'SecretPassword123',
            'password_confirmation' => 'SecretPassword123',
        ]);
        $resDup->assertSessionHasErrors('email');

        // Mismatched password confirmation
        $resMis = $this->post(route('register'), [
            'name' => 'Sai Password',
            'email' => 'different@ticketbox.vn',
            'password' => 'SecretPassword123',
            'password_confirmation' => 'WrongPassword999',
        ]);
        $resMis->assertSessionHasErrors('password');
    });

    test('user is redirected to home when accessing dashboard route', function () {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertRedirect(route('home'));
    });

    test('user can logout successfully', function () {
        $response = $this->actingAs($this->user)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    });
});

/*
|--------------------------------------------------------------------------
| 2. Quản lý Hồ sơ cá nhân (Profile Management)
|--------------------------------------------------------------------------
*/
describe('User Profile Management', function () {
    test('user can view and update their profile details including phone number', function () {
        $response = $this->actingAs($this->user)->get(route('profile.edit'));
        $response->assertOk();
        $response->assertSee('Nguyễn Khách Hàng');
        $response->assertSee('khachhang@ticketbox.vn');

        // Update profile
        $updateRes = $this->actingAs($this->user)->patch(route('profile.update'), [
            'name' => 'Nguyễn Khách Hàng (Đã đổi)',
            'email' => 'khachhang.new@ticketbox.vn',
            'phone' => '0999888777',
        ]);

        $updateRes->assertRedirect(route('profile.edit'));
        $updateRes->assertSessionHas('status', 'profile-updated');

        $this->user->refresh();
        expect($this->user->name)->toBe('Nguyễn Khách Hàng (Đã đổi)')
            ->and($this->user->email)->toBe('khachhang.new@ticketbox.vn')
            ->and($this->user->phone)->toBe('0999888777');
    });

    test('user can change their password with valid current password', function () {
        $response = $this->actingAs($this->user)->put(route('password.update'), [
            'current_password' => 'UserSecret123',
            'password' => 'BrandNewPassword888',
            'password_confirmation' => 'BrandNewPassword888',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'password-updated');

        $this->user->refresh();
        expect(Hash::check('BrandNewPassword888', $this->user->password))->toBeTrue();
    });

    test('user can delete their account by providing correct password', function () {
        $response = $this->actingAs($this->user)->delete(route('profile.destroy'), [
            'password' => 'UserSecret123',
        ]);

        $response->assertRedirect('/');
        $this->assertGuest();
        expect(User::find($this->user->id))->toBeNull();
    });
});

/*
|--------------------------------------------------------------------------
| 3. Bảo vệ Tài khoản bị Khóa (Locked Account Enforcement)
|--------------------------------------------------------------------------
*/
describe('Locked User Boundary Enforcement', function () {
    test('user whose account is locked is logged out immediately on web request', function () {
        $this->actingAs($this->user);
        $this->user->update(['is_active' => false]);

        $response = $this->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    test('locked user receives 403 on AJAX request', function () {
        $this->actingAs($this->user);
        $this->user->update(['is_active' => false]);

        $response = $this->getJson(route('profile.edit'));

        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
    });
});

/*
|--------------------------------------------------------------------------
| 4. Khám phá Sự kiện & Lịch diễn (Public Catalog & Showtimes)
|--------------------------------------------------------------------------
*/
describe('Public Catalog & Showtime Viewing', function () {
    test('user can search events by keyword and filter by category', function () {
        $response = $this->actingAs($this->user)->get(route('events.index', [
            'q' => 'Live Concert Đỉnh Cao',
            'category' => $this->category->id,
        ]));

        $response->assertOk();
        $response->assertSee('Live Concert Đỉnh Cao 2026');
    });

    test('draft event cannot be viewed by regular user or guest (returns 404)', function () {
        $draftEvent = Event::factory()->create([
            'status' => 'draft',
            'slug' => 'bi-mat-noi-bo',
        ]);

        $this->actingAs($this->user)->get(route('events.show', $draftEvent->slug))
            ->assertNotFound();
    });

    test('user can view seat map and seat status of showtime', function () {
        // Seat map view
        $resSeatMap = $this->actingAs($this->user)->get(route('showtimes.seats', $this->showtime));
        $resSeatMap->assertOk();

        // Real-time seat status API
        $resStatus = $this->getJson(route('showtimes.seat-status', $this->showtime));
        $resStatus->assertOk();
        $resStatus->assertJsonStructure(['unavailable']);
    });
});

/*
|--------------------------------------------------------------------------
| 5. Giữ Chỗ & Giỏ Vé (Seat Holds & Cart Operations)
|--------------------------------------------------------------------------
*/
describe('Seat Holds & Cart Workflows', function () {
    test('guest attempting to hold seats is redirected to login', function () {
        $seats = $this->showtime->showtimeSeats()->take(2)->pluck('id')->all();

        $response = $this->post(route('showtimes.holds.store', $this->showtime), [
            'seat_ids' => $seats,
        ]);

        $response->assertRedirect(route('login'));
    });

    test('user can hold seats and view them in cart with real expiration countdown', function () {
        $availableSeats = $this->showtime->showtimeSeats()->take(2)->get();
        $seatIds = $availableSeats->pluck('id')->all();

        $response = $this->actingAs($this->user)->postJson(route('showtimes.holds.store', $this->showtime), [
            'seat_ids' => $seatIds,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'held_until', 'redirect']);

        // Check seats marked as held
        foreach ($availableSeats as $stSeat) {
            $fresh = $stSeat->fresh();
            expect($fresh->status)->toBe('held')
                ->and($fresh->held_by_user_id)->toBe($this->user->id)
                ->and($fresh->held_until)->not->toBeNull();
        }

        // Cart index displays user held seats
        $cartRes = $this->actingAs($this->user)->get(route('cart.index'));
        $cartRes->assertOk();
        $cartRes->assertSee('Live Concert Đỉnh Cao 2026');
        $cartRes->assertViewHas('items', fn ($items) => count($items) === 2);
        $cartRes->assertViewHas('total', 800000);
    });

    test('conflict protection: second user cannot hold already held seats', function () {
        $seatId = $this->showtime->showtimeSeats()->first()->id;

        // User 1 holds seat
        app(SeatHoldService::class)->hold($this->user, $this->showtime, [$seatId]);

        // User 2 attempts to hold same seat
        $user2 = createCustomer();
        $response = $this->actingAs($user2)->postJson(route('showtimes.holds.store', $this->showtime), [
            'seat_ids' => [$seatId],
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
        expect($response->json('message'))->toContain('vừa có người giữ hoặc đã bán');
    });

    test('user cannot hold more than 8 seats in total', function () {
        $allSeatIds = $this->showtime->showtimeSeats()->take(9)->pluck('id')->all();

        $response = $this->actingAs($this->user)->postJson(route('showtimes.holds.store', $this->showtime), [
            'seat_ids' => $allSeatIds,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('seat_ids');
    });

    test('user can remove an individual seat from their cart', function () {
        $stSeat = $this->showtime->showtimeSeats()->first();
        app(SeatHoldService::class)->hold($this->user, $this->showtime, [$stSeat->id]);

        expect($stSeat->fresh()->status)->toBe('held');

        $response = $this->actingAs($this->user)->deleteJson(route('cart.seats.destroy', $stSeat));

        $response->assertOk();
        $response->assertJson(['message' => 'Đã bỏ ghế khỏi giỏ vé.']);

        $fresh = $stSeat->fresh();
        expect($fresh->status)->toBe('available')
            ->and($fresh->held_by_user_id)->toBeNull();
    });

    test('IDOR protection: user cannot remove another user\'s held seat', function () {
        $otherUser = createCustomer();
        $stSeat = $this->showtime->showtimeSeats()->first();
        app(SeatHoldService::class)->hold($otherUser, $this->showtime, [$stSeat->id]);

        $response = $this->actingAs($this->user)->deleteJson(route('cart.seats.destroy', $stSeat));

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Ghế này không nằm trong giỏ vé của bạn.']);
        expect($stSeat->fresh()->status)->toBe('held');
    });
});

/*
|--------------------------------------------------------------------------
| 6. Thanh Toán & Lịch Sử Vé Điện Tử (Checkout & E-Ticket History)
|--------------------------------------------------------------------------
*/
describe('Checkout & E-Ticket History Workflows', function () {
    test('user can complete checkout with multiple supported payment methods', function () {
        $methods = ['vietqr', 'momo', 'zalopay', 'card'];

        foreach ($methods as $method) {
            $seat = $this->showtime->showtimeSeats()->where('status', 'available')->first();
            app(SeatHoldService::class)->hold($this->user, $this->showtime, [$seat->id]);

            $response = $this->actingAs($this->user)->postJson(route('checkout.store'), [
                'payment_method' => $method,
            ]);

            $response->assertOk();
            $response->assertJsonStructure(['booking_code', 'total_price', 'redirect']);

            $booking = Booking::where('booking_code', $response->json('booking_code'))->first();
            expect($booking)->not->toBeNull()
                ->and($booking->user_id)->toBe($this->user->id)
                ->and($booking->status)->toBe('confirmed')
                ->and($booking->payment_method)->toBe($method)
                ->and($seat->fresh()->status)->toBe('booked');
        }
    });

    test('checkout is rejected if cart has expired or is empty', function () {
        $response = $this->actingAs($this->user)->postJson(route('checkout.store'), [
            'payment_method' => 'vietqr',
        ]);

        $response->assertStatus(422);
        expect($response->json('message'))->toContain('Giỏ vé trống hoặc đã hết thời gian');
    });

    test('user can view their own booking history but cannot see other users bookings', function () {
        $otherUser = createCustomer(['name' => 'Người Lạ']);

        // Booking of this user
        $myBooking = Booking::create([
            'user_id' => $this->user->id,
            'booking_code' => 'TBX-MYORDER-77',
            'total_price' => 500000,
            'status' => 'confirmed',
            'payment_method' => 'vietqr',
        ]);
        $stSeat1 = $this->showtime->showtimeSeats()->where('status', 'available')->first();
        BookingItem::create([
            'booking_id' => $myBooking->id,
            'showtime_seat_id' => $stSeat1->id,
            'price' => 500000,
        ]);

        // Booking of another user
        $otherBooking = Booking::create([
            'user_id' => $otherUser->id,
            'booking_code' => 'TBX-OTHERORDER-88',
            'total_price' => 300000,
            'status' => 'confirmed',
            'payment_method' => 'momo',
        ]);
        $stSeat2 = $this->showtime->showtimeSeats()->where('status', 'available')->skip(1)->first();
        BookingItem::create([
            'booking_id' => $otherBooking->id,
            'showtime_seat_id' => $stSeat2->id,
            'price' => 300000,
        ]);

        // Access my history
        $response = $this->actingAs($this->user)->get(route('bookings.history'));
        $response->assertOk();
        $response->assertSee('TBX-MYORDER-77');
        $response->assertDontSee('TBX-OTHERORDER-88');

        // Other user history
        $otherRes = $this->actingAs($otherUser)->get(route('bookings.history'));
        $otherRes->assertOk();
        $otherRes->assertSee('TBX-OTHERORDER-88');
        $otherRes->assertDontSee('TBX-MYORDER-77');
    });
});

/*
|--------------------------------------------------------------------------
| 7. Ranh Giới Phân Quyền User (Role Authorization Boundaries)
|--------------------------------------------------------------------------
*/
describe('User Role Authorization Boundaries', function () {
    test('user is strictly forbidden from accessing any admin URL', function () {
        $adminUrls = [
            '/admin',
            '/admin/categories',
            '/admin/events',
            '/admin/showtimes',
            '/admin/rooms',
            '/admin/bookings',
            '/admin/discounts',
            '/admin/users',
            '/admin/reports',
            '/admin/reports/pdf',
        ];

        foreach ($adminUrls as $url) {
            $this->actingAs($this->user)->get($url)->assertForbidden();
        }
    });

    test('user cannot escalate privileges or update roles directly', function () {
        // Attempting to post to admin user store or patch roles
        $this->actingAs($this->user)
            ->post('/admin/users', ['name' => 'Hacked Admin', 'role' => 'admin'])
            ->assertForbidden();

        $this->actingAs($this->user)
            ->patch('/admin/users/' . $this->user->id . '/toggle-role')
            ->assertForbidden();

        expect($this->user->fresh()->hasRole('user'))->toBeTrue()
            ->and($this->user->fresh()->hasRole('admin'))->toBeFalse();
    });
});
