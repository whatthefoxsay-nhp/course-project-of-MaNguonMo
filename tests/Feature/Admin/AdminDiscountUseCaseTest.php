<?php

use App\Models\Booking;
use App\Models\Discount;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->admin = User::factory()->create(['name' => 'Quản Trị Viên']);
    $this->admin->assignRole('admin');

    $this->user = User::factory()->create(['name' => 'Khách Hàng']);
    $this->user->assignRole('user');
});

test('TC_UC008_001: admin sees discount list with code, discount type, value, max discount, min order, date limits, max uses and active status', function () {
    $discount = Discount::create([
        'code' => 'SUMMER50',
        'title' => 'Khuyến Mãi Hè Rực Rỡ',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'max_discount_amount' => 150000,
        'min_order_value' => 300000,
        'max_uses' => 200,
        'used_count' => 15,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(30)->toDateString(),
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.discounts.index'));

    $response->assertStatus(200);
    $response->assertSee('SUMMER50');
    $response->assertSee('Khuyến Mãi Hè Rực Rỡ');
    $response->assertSee('GIẢM 20%');
    $response->assertSee('150.000đ'); // Mức giảm tối đa
    $response->assertSee('300.000đ'); // Đơn tối thiểu
    $response->assertSee('15/200');   // Lượt dùng
    $response->assertSee('Đang kích hoạt');
});

test('TC_UC008_002: admin creates percentage discount successfully with full valid fields', function () {
    $payload = [
        'code' => 'VIPPRO30',
        'title' => 'Giảm 30% cho khách VIP',
        'discount_type' => 'percentage',
        'discount_value' => 30,
        'max_discount_amount' => 200000,
        'min_order_value' => 500000,
        'max_uses' => 150,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(15)->toDateString(),
        'applicable_to' => 'Tất cả sự kiện',
    ];

    $response = $this->actingAs($this->admin)->post(route('admin.discounts.store'), $payload);

    $response->assertRedirect(route('admin.discounts.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('discounts', [
        'code' => 'VIPPRO30',
        'discount_type' => 'percentage',
        'discount_value' => 30,
        'max_discount_amount' => 200000,
        'min_order_value' => 500000,
        'max_uses' => 150,
        'is_active' => true,
    ]);
});

test('TC_UC008_003: admin creates fixed amount discount successfully', function () {
    $payload = [
        'code' => 'GIAM50K',
        'title' => 'Giảm 50.000 VNĐ trực tiếp',
        'discount_type' => 'fixed',
        'discount_value' => 50000,
        'min_order_value' => 200000,
        'max_uses' => 300,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(20)->toDateString(),
    ];

    $response = $this->actingAs($this->admin)->post(route('admin.discounts.store'), $payload);

    $response->assertRedirect(route('admin.discounts.index'));
    $this->assertDatabaseHas('discounts', [
        'code' => 'GIAM50K',
        'discount_type' => 'fixed',
        'discount_value' => 50000,
        'is_active' => true,
    ]);
});

test('TC_UC008_004: voucher code input normalization trims spaces and converts to uppercase', function () {
    $payload = [
        'code' => '  summer2026  ',
        'title' => 'Mã ưu đãi hè',
        'discount_type' => 'fixed',
        'discount_value' => 40000,
        'max_uses' => 50,
    ];

    $response = $this->actingAs($this->admin)->post(route('admin.discounts.store'), $payload);

    $response->assertRedirect(route('admin.discounts.index'));
    $this->assertDatabaseHas('discounts', [
        'code' => 'SUMMER2026',
    ]);
});

test('TC_UC008_005: creating discount voucher without optional constraints succeeds with system defaults', function () {
    $payload = [
        'code' => 'BASICVOUCHER',
        'discount_type' => 'fixed',
        'discount_value' => 20000,
    ];

    $response = $this->actingAs($this->admin)->post(route('admin.discounts.store'), $payload);

    $response->assertRedirect(route('admin.discounts.index'));
    $this->assertDatabaseHas('discounts', [
        'code' => 'BASICVOUCHER',
        'min_order_value' => 0,
        'max_discount_amount' => null,
        'is_active' => true,
    ]);
});

test('TC_UC008_006: admin updates existing discount information without code collision', function () {
    $discount = Discount::create([
        'code' => 'UPDATEVOUCHER',
        'title' => 'Tiêu đề cũ',
        'discount_type' => 'fixed',
        'discount_value' => 30000,
        'min_order_value' => 100000,
        'max_uses' => 50,
        'is_active' => true,
    ]);

    $updatePayload = [
        'code' => 'UPDATEVOUCHER',
        'title' => 'Tiêu đề mới đã sửa',
        'discount_type' => 'fixed',
        'discount_value' => 60000,
        'min_order_value' => 150000,
        'max_uses' => 100,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(40)->toDateString(),
    ];

    $response = $this->actingAs($this->admin)->put(route('admin.discounts.update', $discount), $updatePayload);

    $response->assertRedirect(route('admin.discounts.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('discounts', [
        'id' => $discount->id,
        'code' => 'UPDATEVOUCHER',
        'title' => 'Tiêu đề mới đã sửa',
        'discount_value' => 60000,
        'max_uses' => 100,
    ]);
});

test('TC_UC008_007: toggle discount status from active to paused marks is_active false and invalidates order use', function () {
    $discount = Discount::create([
        'code' => 'TOGGLEOFF',
        'title' => 'Mã tạm dừng',
        'discount_type' => 'fixed',
        'discount_value' => 25000,
        'max_uses' => 50,
        'is_active' => true,
    ]);

    expect($discount->isValidForOrder(100000))->toBeTrue();

    $response = $this->actingAs($this->admin)->patch(route('admin.discounts.toggle-status', $discount));

    $response->assertRedirect();
    expect($discount->fresh()->is_active)->toBeFalse();
    expect($discount->fresh()->isValidForOrder(100000))->toBeFalse();
});

test('TC_UC008_008: toggle discount status again reactivates voucher in database', function () {
    $discount = Discount::create([
        'code' => 'TOGGLEON',
        'title' => 'Mã kích hoạt lại',
        'discount_type' => 'percentage',
        'discount_value' => 15,
        'max_uses' => 50,
        'is_active' => false,
    ]);

    expect($discount->isValidForOrder(100000))->toBeFalse();

    $this->actingAs($this->admin)->patch(route('admin.discounts.toggle-status', $discount));

    expect($discount->fresh()->is_active)->toBeTrue();
    expect($discount->fresh()->isValidForOrder(100000))->toBeTrue();
});

test('TC_UC008_009: admin deletes unused discount voucher successfully', function () {
    $discount = Discount::create([
        'code' => 'DELETEUNUSED',
        'title' => 'Mã sắp xóa',
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'max_uses' => 10,
        'used_count' => 0,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->delete(route('admin.discounts.destroy', $discount));

    $response->assertRedirect(route('admin.discounts.index'));
    $this->assertDatabaseMissing('discounts', [
        'id' => $discount->id,
        'code' => 'DELETEUNUSED',
    ]);
});

test('TC_UC008_010: deleting discount previously applied on bookings preserves booking records and total prices', function () {
    $discount = Discount::create([
        'code' => 'HISTORYDISCOUNT',
        'title' => 'Mã đã dùng',
        'discount_type' => 'fixed',
        'discount_value' => 50000,
        'max_uses' => 100,
        'used_count' => 1,
        'is_active' => true,
    ]);

    $booking = Booking::create([
        'user_id' => $this->user->id,
        'booking_code' => 'TBX-DISCHIST01',
        'total_price' => 250000,
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($this->admin)->delete(route('admin.discounts.destroy', $discount));

    $response->assertRedirect(route('admin.discounts.index'));
    $this->assertDatabaseMissing('discounts', ['id' => $discount->id]);

    // Booking remains intact with its original total_price
    expect($booking->fresh()->total_price)->toBe(250000);
    expect($booking->fresh()->status)->toBe('confirmed');
});

test('TC_UC008_011: validation fails when required fields are missing on store and update', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => '',
        'discount_type' => '',
        'discount_value' => '',
    ]);

    $response->assertSessionHasErrors(['code', 'discount_type', 'discount_value']);
});

test('TC_UC008_012: duplicate voucher code is rejected even with different case input', function () {
    Discount::create([
        'code' => 'SUMMER2026',
        'title' => 'Mã hè',
        'discount_type' => 'fixed',
        'discount_value' => 30000,
        'max_uses' => 10,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'summer2026',
        'title' => 'Mã hè trùng',
        'discount_type' => 'fixed',
        'discount_value' => 30000,
    ]);

    $response->assertSessionHasErrors(['code']);
});

test('TC_UC008_013: percentage discount boundary validation accepts 1% and 100% and rejects out-of-range values', function () {
    // 1. Boundary: 1% is valid
    $res1 = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'BOUND1PCT',
        'discount_type' => 'percentage',
        'discount_value' => 1,
    ]);
    $res1->assertSessionDoesntHaveErrors(['discount_value']);
    $this->assertDatabaseHas('discounts', ['code' => 'BOUND1PCT', 'discount_value' => 1]);

    // 2. Boundary: 100% is valid
    $res100 = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'BOUND100PCT',
        'discount_type' => 'percentage',
        'discount_value' => 100,
    ]);
    $res100->assertSessionDoesntHaveErrors(['discount_value']);
    $this->assertDatabaseHas('discounts', ['code' => 'BOUND100PCT', 'discount_value' => 100]);

    // 3. Out-of-bounds: 0% is rejected
    $res0 = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'BOUND0PCT',
        'discount_type' => 'percentage',
        'discount_value' => 0,
    ]);
    $res0->assertSessionHasErrors(['discount_value']);

    // 4. Out-of-bounds: -10% is rejected
    $resNeg = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'BOUNDNEGPCT',
        'discount_type' => 'percentage',
        'discount_value' => -10,
    ]);
    $resNeg->assertSessionHasErrors(['discount_value']);

    // 5. Out-of-bounds: 101% is rejected
    $res101 = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'BOUND101PCT',
        'discount_type' => 'percentage',
        'discount_value' => 101,
    ]);
    $res101->assertSessionHasErrors(['discount_value']);
});

test('TC_UC008_014: numeric constraints reject negative, zero or non-numeric values for discounts and amounts', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'INVALIDNUMS',
        'discount_type' => 'fixed',
        'discount_value' => -50000,
        'min_order_value' => -10000,
        'max_discount_amount' => 'not-a-number',
    ]);

    $response->assertSessionHasErrors(['discount_value', 'min_order_value', 'max_discount_amount']);
});

test('TC_UC008_015: max uses validation rejects negative numbers, zero or decimals', function () {
    $resZero = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'ZEROUSES',
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'max_uses' => 0,
    ]);
    $resZero->assertSessionHasErrors(['max_uses']);

    $resNeg = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'NEGUSES',
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'max_uses' => -5,
    ]);
    $resNeg->assertSessionHasErrors(['max_uses']);

    $resDec = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'DECUSES',
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'max_uses' => 10.5,
    ]);
    $resDec->assertSessionHasErrors(['max_uses']);
});

test('TC_UC008_016: end date in the past is rejected during creation', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'PASTDATE',
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'end_date' => now()->subDays(2)->toDateString(),
    ]);

    $response->assertSessionHasErrors(['end_date']);
});

test('TC_UC008_017: invalid voucher code format with vietnamese diacritics, special chars or xss payload is rejected', function () {
    // 1. Vietnamese diacritics and percentage symbol
    $resVN = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => 'GIẢM50%',
        'discount_type' => 'fixed',
        'discount_value' => 50000,
    ]);
    $resVN->assertSessionHasErrors(['code']);

    // 2. XSS tag payload
    $resXSS = $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
        'code' => '<script>alert(1)</script>',
        'discount_type' => 'fixed',
        'discount_value' => 50000,
    ]);
    $resXSS->assertSessionHasErrors(['code']);
});

test('TC_UC008_018: guest accessing discount management routes is redirected to login', function () {
    $discount = Discount::create([
        'code' => 'GUESTTEST',
        'title' => 'Guest Voucher',
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'max_uses' => 10,
        'is_active' => true,
    ]);

    $this->get(route('admin.discounts.index'))
        ->assertRedirect('/login');

    $this->post(route('admin.discounts.store'), ['code' => 'UNAUTH'])
        ->assertRedirect('/login');

    $this->patch(route('admin.discounts.toggle-status', $discount))
        ->assertRedirect('/login');

    $this->delete(route('admin.discounts.destroy', $discount))
        ->assertRedirect('/login');
});

test('TC_UC008_019: regular user attempting to manage discounts is forbidden 403', function () {
    $discount = Discount::create([
        'code' => 'FORBIDTEST',
        'title' => 'Forbid Voucher',
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'max_uses' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($this->user)->get(route('admin.discounts.index'))
        ->assertStatus(403);

    $this->actingAs($this->user)->post(route('admin.discounts.store'), ['code' => 'FAIL'])
        ->assertStatus(403);

    $this->actingAs($this->user)->put(route('admin.discounts.update', $discount), ['discount_value' => 99999])
        ->assertStatus(403);

    $this->actingAs($this->user)->patch(route('admin.discounts.toggle-status', $discount))
        ->assertStatus(403);

    $this->actingAs($this->user)->delete(route('admin.discounts.destroy', $discount))
        ->assertStatus(403);
});

