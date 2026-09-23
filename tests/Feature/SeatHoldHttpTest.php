<?php

use App\Models\Event;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;

beforeEach(fn () => $this->seed());

function concertShowtime(): Showtime
{
    return Event::firstWhere('slug', 'live-concert-anh-trai-vuot-ngan-chong-gai-2026')
        ->showtimes()->orderBy('start_time')->first();
}

function freeSeatIds(Showtime $showtime, int $count): array
{
    return $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take($count)->pluck('id')->all();
}

test('guests must log in before holding seats', function () {
    $showtime = concertShowtime();

    $this->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => freeSeatIds($showtime, 1)])
        ->assertUnauthorized();
});

test('a customer holds seats and is sent to the cart', function () {
    $showtime = concertShowtime();
    $ids = freeSeatIds($showtime, 2);

    $this->actingAs(createCustomer())
        ->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => $ids])
        ->assertOk()
        ->assertJsonPath('redirect', route('cart.index'))
        ->assertJsonStructure(['message', 'held_until', 'redirect']);

    expect(ShowtimeSeat::whereIn('id', $ids)->where('status', 'held')->count())->toBe(2);
});

test('the second customer gets a clear conflict message', function () {
    $showtime = concertShowtime();
    $ids = freeSeatIds($showtime, 1);
    $this->actingAs(createCustomer())->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => $ids])->assertOk();

    $seatCode = ShowtimeSeat::find($ids[0])->seat->full_code;

    $this->actingAs(createCustomer())
        ->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => $ids])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => "Ghế {$seatCode} vừa có người giữ hoặc đã bán. Vui lòng chọn ghế khác."]);
});

test('an empty selection is rejected', function () {
    $this->actingAs(createCustomer())
        ->postJson(route('showtimes.holds.store', concertShowtime()), ['seat_ids' => [], 'tiers' => []])
        ->assertStatus(422);
});

test('standing tickets can be held by quantity', function () {
    $this->actingAs(createCustomer())
        ->postJson(route('showtimes.holds.store', concertShowtime()), ['tiers' => ['standing_pit' => 2]])
        ->assertOk();

    expect(ShowtimeSeat::where('status', 'held')->count())->toBe(2);
});

test('a customer removes a seat from their cart', function () {
    $showtime = concertShowtime();
    $user = createCustomer();
    [$id] = freeSeatIds($showtime, 1);
    $this->actingAs($user)->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => [$id]]);

    $this->actingAs($user)->deleteJson(route('cart.seats.destroy', $id))->assertOk();

    expect(ShowtimeSeat::find($id)->status)->toBe('available');
});

test('a customer cannot remove somebody else\'s seat', function () {
    $showtime = concertShowtime();
    [$id] = freeSeatIds($showtime, 1);
    $this->actingAs(createCustomer())->postJson(route('showtimes.holds.store', $showtime), ['seat_ids' => [$id]]);

    $this->actingAs(createCustomer())->deleteJson(route('cart.seats.destroy', $id))->assertStatus(422);

    expect(ShowtimeSeat::find($id)->status)->toBe('held');
});

test('seat status lists sold and actively held seats', function () {
    $showtime = concertShowtime();
    [$sold, $held, $expired] = freeSeatIds($showtime, 3);
    ShowtimeSeat::whereKey($sold)->update(['status' => 'booked']);
    ShowtimeSeat::whereKey($held)->update(['status' => 'held', 'held_until' => now()->addMinutes(5)]);
    ShowtimeSeat::whereKey($expired)->update(['status' => 'held', 'held_until' => now()->subMinute()]);

    $unavailable = $this->getJson(route('showtimes.seat-status', $showtime))->assertOk()->json('unavailable');

    expect($unavailable)->toContain($sold, $held)->not->toContain($expired);
});

test('the release command frees expired holds', function () {
    $showtime = concertShowtime();
    [$id] = freeSeatIds($showtime, 1);
    ShowtimeSeat::whereKey($id)->update(['status' => 'held', 'held_until' => now()->subMinute(), 'held_by_user_id' => User::first()->id]);

    $this->artisan('seats:release-expired')->assertSuccessful();

    expect(ShowtimeSeat::find($id)->status)->toBe('available');
});
