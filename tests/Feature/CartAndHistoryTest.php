<?php

use App\Models\Event;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\SeatHoldService;

beforeEach(fn () => $this->seed());

function holdForCart(User $user, int $count): void
{
    $showtime = Event::firstWhere('slug', 'hoa-nhac-giao-huong-saigon-philharmonic')->showtimes()->orderBy('start_time')->first();
    $ids = $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take($count)->pluck('id')->all();
    app(SeatHoldService::class)->hold($user, $showtime, $ids);
}

test('the cart shows the seats the user is holding', function () {
    $user = createCustomer();
    holdForCart($user, 2);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Đêm Hòa Nhạc Giao Hưởng')
        ->assertViewHas('items', fn (array $items) => count($items) === 2)
        ->assertViewHas('expiresInSeconds', fn (int $seconds) => $seconds > 500 && $seconds <= 600);
});

test('expired holds are not shown in the cart', function () {
    $user = createCustomer();
    holdForCart($user, 1);
    ShowtimeSeat::where('held_by_user_id', $user->id)->update(['held_until' => now()->subMinute()]);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Giỏ vé của bạn đang trống')
        ->assertViewHas('items', []);
});

test('booking history lists only the user\'s own bookings from the database', function () {
    $demo = User::firstWhere('email', 'user@ticketbox.vn');

    $this->actingAs($demo)->get(route('bookings.history'))
        ->assertOk()
        ->assertSee('TBX-89214')
        ->assertSee('TBX-77102')
        ->assertViewHas('bookings', fn (array $bookings) => count($bookings) === 2 && count($bookings[0]->seats) >= 1);

    $this->actingAs(createCustomer())->get(route('bookings.history'))
        ->assertOk()
        ->assertDontSee('TBX-89214');
});
