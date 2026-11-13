<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = createAdmin();
    $this->customer = createCustomer();
});

function createTestBooking(User $user, array $overrides = []): Booking
{
    return Booking::create(array_merge([
        'user_id' => $user->id,
        'booking_code' => 'TBX-' . strtoupper(substr(uniqid(), -6)),
        'total_price' => 250000,
        'status' => 'confirmed',
        'payment_method' => 'vietqr',
    ], $overrides));
}

/*
|--------------------------------------------------------------------------
| 1. Phân Quyền Admin (Role Authorization & Security Checks)
|--------------------------------------------------------------------------
*/
describe('Admin Role Authorization & Route Protection', function () {
    test('guest is redirected to login for all admin modules', function () {
        $category = Category::factory()->create();
        $event = Event::factory()->for($category)->create();
        $room = Room::factory()->create();
        $showtime = Showtime::factory()->for($room)->for($event)->create();
        $booking = createTestBooking($this->customer);
        $discount = Discount::create([
            'code' => 'GUESTTEST',
            'title' => 'Guest test',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'max_uses' => 10,
        ]);
        $targetUser = User::factory()->create();

        // GET routes redirect to login
        $this->get(route('admin.dashboard'))->assertRedirect('/login');
        $this->get(route('admin.categories.index'))->assertRedirect('/login');
        $this->get(route('admin.categories.create'))->assertRedirect('/login');
        $this->get(route('admin.events.index'))->assertRedirect('/login');
        $this->get(route('admin.events.create'))->assertRedirect('/login');
        $this->get(route('admin.showtimes.index'))->assertRedirect('/login');
        $this->get(route('admin.showtimes.create'))->assertRedirect('/login');
        $this->get(route('admin.bookings.index'))->assertRedirect('/login');
        $this->get(route('admin.bookings.show', $booking))->assertRedirect('/login');
        $this->get(route('admin.discounts.index'))->assertRedirect('/login');
        $this->get(route('admin.users.index'))->assertRedirect('/login');
        $this->get(route('admin.reports.index'))->assertRedirect('/login');
        $this->get(route('admin.reports.pdf'))->assertRedirect('/login');

        // Mutating routes redirect to login
        $this->post(route('admin.categories.store'), ['name' => 'Test'])->assertRedirect('/login');
        $this->put(route('admin.categories.update', $category), ['name' => 'Updated'])->assertRedirect('/login');
        $this->delete(route('admin.categories.destroy', $category))->assertRedirect('/login');
        $this->post(route('admin.categories.bulk-destroy'), ['ids' => [$category->id]])->assertRedirect('/login');

        $this->delete(route('admin.events.destroy', $event))->assertRedirect('/login');
        $this->delete(route('admin.showtimes.destroy', $showtime))->assertRedirect('/login');
        $this->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled'])->assertRedirect('/login');
        $this->patch(route('admin.discounts.toggle-status', $discount))->assertRedirect('/login');
        $this->delete(route('admin.discounts.destroy', $discount))->assertRedirect('/login');

        $this->patch(route('admin.users.toggle-status', $targetUser))->assertRedirect('/login');
        $this->patch(route('admin.users.toggle-role', $targetUser))->assertRedirect('/login');
        $this->post(route('admin.users.reset-password', $targetUser))->assertRedirect('/login');
    });

    test('regular authenticated user is forbidden (403) from all admin CRUD actions', function () {
        $category = Category::factory()->create();
        $event = Event::factory()->for($category)->create();
        $room = Room::factory()->create();
        $showtime = Showtime::factory()->for($room)->for($event)->create();
        $booking = createTestBooking($this->customer);
        $discount = Discount::create([
            'code' => 'USERFORBIDDEN',
            'title' => 'Forbidden test',
            'discount_type' => 'fixed',
            'discount_value' => 20000,
            'max_uses' => 5,
        ]);
        $targetUser = User::factory()->create();

        $acting = $this->actingAs($this->customer);

        // GET index & create/edit
        $acting->get(route('admin.dashboard'))->assertForbidden();
        $acting->get(route('admin.categories.index'))->assertForbidden();
        $acting->get(route('admin.categories.create'))->assertForbidden();
        $acting->get(route('admin.events.index'))->assertForbidden();
        $acting->get(route('admin.events.create'))->assertForbidden();
        $acting->get(route('admin.showtimes.index'))->assertForbidden();
        $acting->get(route('admin.showtimes.create'))->assertForbidden();
        $acting->get(route('admin.bookings.index'))->assertForbidden();
        $acting->get(route('admin.bookings.show', $booking))->assertForbidden();
        $acting->get(route('admin.discounts.index'))->assertForbidden();
        $acting->get(route('admin.users.index'))->assertForbidden();
        $acting->get(route('admin.reports.index'))->assertForbidden();
        $acting->get(route('admin.reports.pdf'))->assertForbidden();

        // Mutating actions
        $acting->post(route('admin.categories.store'), ['name' => 'Attack Category'])->assertForbidden();
        $acting->put(route('admin.categories.update', $category), ['name' => 'Attack Update'])->assertForbidden();
        $acting->delete(route('admin.categories.destroy', $category))->assertForbidden();
        $acting->post(route('admin.categories.bulk-destroy'), ['ids' => [$category->id]])->assertForbidden();

        $acting->post(route('admin.events.store'), ['title' => 'Attack Event'])->assertForbidden();
        $acting->put(route('admin.events.update', $event), ['title' => 'Attack Update'])->assertForbidden();
        $acting->delete(route('admin.events.destroy', $event))->assertForbidden();

        $acting->post(route('admin.showtimes.store'), ['base_price' => 100000])->assertForbidden();
        $acting->put(route('admin.showtimes.update', $showtime), ['base_price' => 100000])->assertForbidden();
        $acting->delete(route('admin.showtimes.destroy', $showtime))->assertForbidden();

        $acting->patch(route('admin.bookings.update-status', $booking), ['status' => 'cancelled'])->assertForbidden();

        $acting->post(route('admin.discounts.store'), ['code' => 'ATTACK'])->assertForbidden();
        $acting->put(route('admin.discounts.update', $discount), ['title' => 'Attack'])->assertForbidden();
        $acting->delete(route('admin.discounts.destroy', $discount))->assertForbidden();
        $acting->patch(route('admin.discounts.toggle-status', $discount))->assertForbidden();

        $acting->post(route('admin.users.store'), ['name' => 'Fake Admin'])->assertForbidden();
        $acting->patch(route('admin.users.toggle-status', $targetUser))->assertForbidden();
        $acting->patch(route('admin.users.toggle-role', $targetUser))->assertForbidden();
        $acting->post(route('admin.users.reset-password', $targetUser))->assertForbidden();
    });

    test('admin has full access to all admin management views', function () {
        $category = Category::factory()->create();
        $event = Event::factory()->for($category)->create();
        $room = Room::factory()->create();
        $showtime = Showtime::factory()->for($room)->for($event)->create();
        $booking = createTestBooking($this->customer);

        $acting = $this->actingAs($this->admin);

        $acting->get(route('admin.dashboard'))->assertOk();
        $acting->get(route('admin.categories.index'))->assertOk();
        $acting->get(route('admin.categories.create'))->assertOk();
        $acting->get(route('admin.categories.edit', $category))->assertOk();
        $acting->get(route('admin.events.index'))->assertOk();
        $acting->get(route('admin.events.create'))->assertOk();
        $acting->get(route('admin.events.edit', $event))->assertOk();
        $acting->get(route('admin.showtimes.index'))->assertOk();
        $acting->get(route('admin.showtimes.create'))->assertOk();
        $acting->get(route('admin.showtimes.edit', $showtime))->assertOk();
        $acting->get(route('admin.bookings.index'))->assertOk();
        $acting->get(route('admin.bookings.show', $booking))->assertOk();
        $acting->get(route('admin.discounts.index'))->assertOk();
        $acting->get(route('admin.users.index'))->assertOk();
        $acting->get(route('admin.reports.index'))->assertOk();
    });
});

/*
|--------------------------------------------------------------------------
| 2. Chức Năng Quản Lý Danh Mục (Categories CRUD)
|--------------------------------------------------------------------------
*/
describe('Categories CRUD Operations', function () {
    test('admin can create category with description and custom slug', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'Triển Lãm Nghệ Thuật',
            'slug' => 'trien-lam-nghe-thuat',
            'description' => 'Không gian trưng bày các tác phẩm nghệ thuật đa phương tiện.',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Triển Lãm Nghệ Thuật',
            'slug' => 'trien-lam-nghe-thuat',
        ]);
    });

    test('admin can update category details', function () {
        $category = Category::factory()->create([
            'name' => 'Concert Cũ',
            'slug' => 'concert-cu',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
            'name' => 'Concert Đỉnh Cao',
            'description' => 'Mô tả mới được cập nhật',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');

        $category->refresh();
        expect($category->name)->toBe('Concert Đỉnh Cao')
            ->and($category->description)->toBe('Mô tả mới được cập nhật');
    });

    test('admin can bulk delete empty categories while preserving categories with events', function () {
        $emptyCat1 = Category::factory()->create(['name' => 'Empty 1']);
        $emptyCat2 = Category::factory()->create(['name' => 'Empty 2']);
        $usedCat = Category::factory()->create(['name' => 'Used With Events']);
        Event::factory()->for($usedCat)->create();

        $response = $this->actingAs($this->admin)->post(route('admin.categories.bulk-destroy'), [
            'ids' => [$emptyCat1->id, $emptyCat2->id, $usedCat->id],
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $emptyCat1->id]);
        $this->assertDatabaseMissing('categories', ['id' => $emptyCat2->id]);
        $this->assertDatabaseHas('categories', ['id' => $usedCat->id]);
    });

    test('bulk destroy rejects empty selection', function () {
        $response = $this->actingAs($this->admin)->post(route('admin.categories.bulk-destroy'), [
            'ids' => [],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

/*
|--------------------------------------------------------------------------
| 3. Chức Năng Quản Lý Sự Kiện (Events CRUD)
|--------------------------------------------------------------------------
*/
describe('Events CRUD Operations', function () {
    test('admin can filter events by search query, category, and status', function () {
        $catConcert = Category::factory()->create(['name' => 'Concert']);
        $catTalk = Category::factory()->create(['name' => 'Talkshow']);

        $eventPublished = Event::factory()->create([
            'category_id' => $catConcert->id,
            'title' => 'Liveshow Bật Tình Yêu',
            'status' => 'published',
        ]);

        $eventDraft = Event::factory()->create([
            'category_id' => $catTalk->id,
            'title' => 'Hội Thảo Khởi Nghiệp AI',
            'status' => 'draft',
        ]);

        // Filter by title query
        $resQuery = $this->actingAs($this->admin)->get(route('admin.events.index', ['search' => 'Bật Tình Yêu']));
        $resQuery->assertOk();
        $resQuery->assertSee('Liveshow Bật Tình Yêu');
        $resQuery->assertDontSee('Hội Thảo Khởi Nghiệp AI');

        // Filter by category
        $resCat = $this->actingAs($this->admin)->get(route('admin.events.index', ['category_id' => $catTalk->id]));
        $resCat->assertOk();
        $resCat->assertSee('Hội Thảo Khởi Nghiệp AI');
        $resCat->assertDontSee('Liveshow Bật Tình Yêu');

        // Filter by status
        $resDraft = $this->actingAs($this->admin)->get(route('admin.events.index', ['status' => 'draft']));
        $resDraft->assertOk();
        $resDraft->assertSee('Hội Thảo Khởi Nghiệp AI');
        $resDraft->assertDontSee('Liveshow Bật Tình Yêu');
    });

    test('admin can update event without altering poster when no file is uploaded', function () {
        $event = Event::factory()->create([
            'title' => 'Tiêu đề ban đầu',
            'poster_path' => 'https://example.com/poster.jpg',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.events.update', $event), [
            'title' => 'Tiêu đề sau khi chỉnh sửa',
            'category_id' => $event->category_id,
            'status' => 'published',
            'is_seated' => '1',
            'duration_minutes' => 120,
            'release_date' => now()->toDateString(),
            'description' => 'Mô tả mới',
        ]);

        $response->assertRedirect(route('admin.events.index'));
        $response->assertSessionHas('success');

        $event->refresh();
        expect($event->title)->toBe('Tiêu đề sau khi chỉnh sửa')
            ->and($event->poster_path)->toBe('https://example.com/poster.jpg');
    });
});

/*
|--------------------------------------------------------------------------
| 4. Chức Năng Quản Lý Suất Diễn (Showtimes CRUD)
|--------------------------------------------------------------------------
*/
describe('Showtimes CRUD Operations', function () {
    test('admin can update showtime room when there are no held or booked seats', function () {
        $room1 = Room::factory()->create();
        app(RoomSeatGenerator::class)->generate($room1, 'custom_grid', ['rows' => 2, 'cols' => 4]);

        $room2 = Room::factory()->create();
        app(RoomSeatGenerator::class)->generate($room2, 'custom_grid', ['rows' => 3, 'cols' => 4]);

        $event = Event::factory()->create();
        $start = now()->addDays(4)->setTime(18, 0);

        $showtime = Showtime::create([
            'event_id' => $event->id,
            'room_id' => $room1->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addHours(2),
            'base_price' => 200000,
        ]);
        app(ShowtimeSeatGenerator::class)->generate($showtime);

        expect($showtime->showtimeSeats()->count())->toBe(8);

        // Update to room 2
        $response = $this->actingAs($this->admin)->put(route('admin.showtimes.update', $showtime), [
            'event_id' => $event->id,
            'room_id' => $room2->id,
            'start_time' => $start->format('Y-m-d\TH:i'),
            'end_time' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
            'base_price' => 220000,
        ]);

        $response->assertRedirect(route('admin.showtimes.index'));
        $response->assertSessionHas('success');

        $showtime->refresh();
        expect($showtime->room_id)->toBe($room2->id)
            ->and($showtime->base_price)->toBe(220000)
            ->and($showtime->showtimeSeats()->count())->toBe(12);
    });
});

/*
|--------------------------------------------------------------------------
| 5. Chức Năng Quản Lý Đơn Đặt Vé (Bookings Management)
|--------------------------------------------------------------------------
*/
describe('Bookings Management Operations', function () {
    test('admin can filter bookings by search code and status', function () {
        $userA = User::factory()->create(['name' => 'Tran Van A', 'email' => 'a@ticket.vn']);
        $userB = User::factory()->create(['name' => 'Le Thi B', 'email' => 'b@ticket.vn']);

        $bookingA = createTestBooking($userA, [
            'booking_code' => 'TBX-AAA111',
            'total_price' => 500000,
            'status' => 'confirmed',
            'payment_method' => 'vietqr',
        ]);

        $bookingB = createTestBooking($userB, [
            'booking_code' => 'TBX-BBB222',
            'total_price' => 300000,
            'status' => 'pending',
            'payment_method' => 'momo',
        ]);

        // Filter by code
        $resCode = $this->actingAs($this->admin)->get(route('admin.bookings.index', ['search' => 'TBX-AAA111']));
        $resCode->assertOk();
        $resCode->assertSee('TBX-AAA111');
        $resCode->assertDontSee('TBX-BBB222');

        // Filter by status
        $resPending = $this->actingAs($this->admin)->get(route('admin.bookings.index', ['status' => 'pending']));
        $resPending->assertOk();
        $resPending->assertSee('TBX-BBB222');
        $resPending->assertDontSee('TBX-AAA111');
    });

    test('admin can view booking json details', function () {
        $booking = createTestBooking($this->customer, [
            'booking_code' => 'TBX-JSON-TEST',
            'total_price' => 450000,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.bookings.show', $booking));

        $response->assertOk();
        $response->assertJson([
            'booking_code' => 'TBX-JSON-TEST',
            'total_price' => 450000,
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| 6. Chức Năng Quản Lý Mã Giảm Giá & Voucher (Discounts CRUD)
|--------------------------------------------------------------------------
*/
describe('Discounts CRUD Operations', function () {
    test('admin can update an existing discount voucher', function () {
        $discount = Discount::create([
            'code' => 'PROMO50',
            'title' => 'Giảm 50k',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'min_order_value' => 200000,
            'max_uses' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.discounts.update', $discount), [
            'title' => 'Giảm 70k Đêm Nhạc',
            'discount_type' => 'fixed',
            'discount_value' => 70000,
            'min_order_value' => 300000,
            'max_uses' => 200,
            'applicable_to' => 'Đại nhạc hội',
        ]);

        $response->assertRedirect(route('admin.discounts.index'));
        $response->assertSessionHas('success');

        $discount->refresh();
        expect($discount->title)->toBe('Giảm 70k Đêm Nhạc')
            ->and($discount->discount_value)->toBe(70000)
            ->and($discount->min_order_value)->toBe(300000)
            ->and($discount->max_uses)->toBe(200);
    });

    test('discount validation rejects duplicate code and invalid date range', function () {
        Discount::create([
            'code' => 'EXISTINGCODE',
            'title' => 'Code đã có',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'max_uses' => 10,
        ]);

        // Duplicate code
        $resDup = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
            'code' => 'EXISTINGCODE',
            'title' => 'Thử tạo trùng',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'max_uses' => 50,
        ]);
        $resDup->assertSessionHasErrors('code');

        // End date before start date
        $resDate = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
            'code' => 'INVALDATE',
            'title' => 'Lỗi ngày',
            'discount_type' => 'fixed',
            'discount_value' => 20000,
            'max_uses' => 50,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);
        $resDate->assertSessionHasErrors('end_date');
    });
});

/*
|--------------------------------------------------------------------------
| 7. Chức Năng Quản Lý Người Dùng & Phân Quyền (Users Management)
|--------------------------------------------------------------------------
*/
describe('Users Management & Account Security', function () {
    test('admin cannot demote their own admin role', function () {
        $response = $this->actingAs($this->admin)->patch(route('admin.users.toggle-role', $this->admin));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        expect($this->admin->fresh()->hasRole('admin'))->toBeTrue();
    });

    test('admin cannot lock their own account via ajax', function () {
        $response = $this->actingAs($this->admin)->patchJson(route('admin.users.toggle-status', $this->admin));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        expect($this->admin->fresh()->is_active)->toBeTrue();
    });

    test('admin can promote customer to admin and demote another admin to customer', function () {
        $staff = User::factory()->create();
        $staff->assignRole('user');

        // Promote to admin
        $this->actingAs($this->admin)->patch(route('admin.users.toggle-role', $staff));
        expect($staff->fresh()->hasRole('admin'))->toBeTrue();

        // Demote back to user
        $this->actingAs($this->admin)->patch(route('admin.users.toggle-role', $staff));
        expect($staff->fresh()->hasRole('user'))->toBeTrue();
    });

    test('admin can reset user password and user can authenticate with the new temporary password', function () {
        $user = User::factory()->create([
            'email' => 'forgotten@ticketbox.vn',
            'password' => 'oldSecretPassword123',
        ]);

        $this->actingAs($this->admin)->post(route('admin.users.reset-password', $user));

        $tempPassword = 'TicketBox@'.date('Y');
        $user->refresh();

        expect(Hash::check($tempPassword, $user->password))->toBeTrue();

        // Check user can authenticate with this temp password
        $authAttempt = auth()->attempt(['email' => 'forgotten@ticketbox.vn', 'password' => $tempPassword]);
        expect($authAttempt)->toBeTrue();
    });
});
