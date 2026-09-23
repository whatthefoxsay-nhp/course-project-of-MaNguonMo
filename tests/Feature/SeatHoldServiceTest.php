<?php

use App\Exceptions\SeatHoldException;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\RoomSeatGenerator;
use App\Services\SeatHoldService;
use App\Services\ShowtimeSeatGenerator;

/** Suất diễn có ghế, phòng lưới 3 hàng x 4 ghế (hàng A,B = svip_diamond, C = vip_gold), giá cơ bản 200.000đ. */
function seatedShowtime(string $preset = 'custom_grid', array $eventAttributes = []): Showtime
{
    $room = Room::factory()->create(['layout_preset' => $preset]);
    app(RoomSeatGenerator::class)->generate($room, $preset, ['rows' => 3, 'cols' => 4]);
    $event = Event::factory()->create(array_merge(['is_seated' => true], $eventAttributes));
    $showtime = Showtime::factory()->for($room)->for($event)->create(['base_price' => 200000]);
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    return $showtime;
}

function seatIds(Showtime $showtime, int $count): array
{
    return $showtime->showtimeSeats()->orderBy('id')->take($count)->pluck('id')->all();
}

function holds(): SeatHoldService
{
    return app(SeatHoldService::class);
}

test('holding seats marks them held for ten minutes at the tier price', function () {
    $showtime = seatedShowtime();
    $user = createCustomer();

    $held = holds()->hold($user, $showtime, seatIds($showtime, 2));

    expect($held)->toHaveCount(2);
    $held->each(function (ShowtimeSeat $seat) use ($user) {
        $seat->refresh();
        expect($seat->status)->toBe('held')
            ->and($seat->held_by_user_id)->toBe($user->id)
            ->and($seat->held_until->between(now()->addMinutes(9), now()->addMinutes(11)))->toBeTrue()
            ->and($seat->price_override)->toBe(350000); // svip_diamond = 200.000 + 150.000
    });
});

test('a seat held by someone else cannot be held again (no double booking)', function () {
    $showtime = seatedShowtime();
    [$seatId] = seatIds($showtime, 1);
    $first = createCustomer();

    holds()->hold($first, $showtime, [$seatId]);

    expect(fn () => holds()->hold(createCustomer(), $showtime, [$seatId]))
        ->toThrow(SeatHoldException::class, 'vừa có người giữ');

    expect(ShowtimeSeat::find($seatId)->held_by_user_id)->toBe($first->id);
});

test('holding is all or nothing', function () {
    $showtime = seatedShowtime();
    [$free, $taken] = seatIds($showtime, 2);
    holds()->hold(createCustomer(), $showtime, [$taken]);

    expect(fn () => holds()->hold(createCustomer(), $showtime, [$free, $taken]))
        ->toThrow(SeatHoldException::class);

    expect(ShowtimeSeat::find($free)->status)->toBe('available');
});

test('an expired hold can be taken by another user', function () {
    $showtime = seatedShowtime();
    [$seatId] = seatIds($showtime, 1);
    ShowtimeSeat::whereKey($seatId)->update(['status' => 'held', 'held_by_user_id' => createCustomer()->id, 'held_until' => now()->subMinute()]);
    $second = createCustomer();

    holds()->hold($second, $showtime, [$seatId]);

    expect(ShowtimeSeat::find($seatId)->held_by_user_id)->toBe($second->id);
});

test('booked seats cannot be held', function () {
    $showtime = seatedShowtime();
    [$seatId] = seatIds($showtime, 1);
    ShowtimeSeat::whereKey($seatId)->update(['status' => 'booked']);

    expect(fn () => holds()->hold(createCustomer(), $showtime, [$seatId]))->toThrow(SeatHoldException::class);
});

test('at most eight seats per user and showtime', function () {
    $showtime = seatedShowtime();
    $user = createCustomer();
    holds()->hold($user, $showtime, seatIds($showtime, 6));

    $nextThree = $showtime->showtimeSeats()->where('status', 'available')->orderBy('id')->take(3)->pluck('id')->all();

    expect(fn () => holds()->hold($user, $showtime, $nextThree))->toThrow(SeatHoldException::class, 'tối đa 8');
});

test('holding in a new showtime releases the previous one', function () {
    $first = seatedShowtime();
    $second = seatedShowtime();
    $user = createCustomer();
    [$oldSeat] = seatIds($first, 1);

    holds()->hold($user, $first, [$oldSeat]);
    holds()->hold($user, $second, seatIds($second, 1));

    expect(ShowtimeSeat::find($oldSeat)->status)->toBe('available')
        ->and(ShowtimeSeat::find($oldSeat)->price_override)->toBeNull();
});

test('seats of another showtime are rejected', function () {
    $showtime = seatedShowtime();
    $other = seatedShowtime();

    expect(fn () => holds()->hold(createCustomer(), $showtime, seatIds($other, 1)))
        ->toThrow(SeatHoldException::class, 'không thuộc suất diễn');
});

test('a showtime that already started cannot be booked', function () {
    $showtime = seatedShowtime();
    $showtime->update(['start_time' => now()->subMinute()]);

    expect(fn () => holds()->hold(createCustomer(), $showtime, seatIds($showtime, 1)))
        ->toThrow(SeatHoldException::class, 'đã bắt đầu');
});

test('non seated events sell passes and the server assigns seats', function () {
    $showtime = seatedShowtime('convention_center', ['is_seated' => false]);
    $user = createCustomer();

    $held = holds()->hold($user, $showtime, [], ['vip_pass' => 2, 'combo_pass' => 1]);

    expect($held)->toHaveCount(4)
        ->and($held->where('price_override', 300000)->count())->toBe(2)  // vip_pass = 200.000 + 100.000
        ->and($held->where('price_override', 170000)->count())->toBe(2); // combo 2 người = 340.000 / 2
});

test('non seated events reject hand picked seats and unknown tiers', function () {
    $showtime = seatedShowtime('convention_center', ['is_seated' => false]);

    expect(fn () => holds()->hold(createCustomer(), $showtime, seatIds($showtime, 1)))->toThrow(SeatHoldException::class)
        ->and(fn () => holds()->hold(createCustomer(), $showtime, [], ['svip_diamond' => 1]))->toThrow(SeatHoldException::class, 'Hạng vé không hợp lệ');
});

test('a pass tier fails when there are not enough seats left', function () {
    $showtime = seatedShowtime('convention_center', ['is_seated' => false]);
    $showtime->showtimeSeats()->update(['status' => 'booked']);

    expect(fn () => holds()->hold(createCustomer(), $showtime, [], ['standard_pass' => 1]))
        ->toThrow(SeatHoldException::class, 'chỉ còn 0 chỗ');
});

test('standing tickets are assigned from the hidden standing row', function () {
    $showtime = seatedShowtime('mega_concert');

    $held = holds()->hold(createCustomer(), $showtime, [], ['standing_pit' => 3]);

    expect($held)->toHaveCount(3)
        ->and($held->every(fn ($seat) => $seat->seat->type === 'standing_pit'))->toBeTrue();
});

test('a user can release only their own hold', function () {
    $showtime = seatedShowtime();
    $owner = createCustomer();
    $held = holds()->hold($owner, $showtime, seatIds($showtime, 1))->first();

    expect(fn () => holds()->release(createCustomer(), $held))->toThrow(SeatHoldException::class);

    holds()->release($owner, $held);
    expect($held->fresh()->status)->toBe('available');
});

test('expired holds are released in bulk', function () {
    $showtime = seatedShowtime();
    [$expired, $active] = seatIds($showtime, 2);
    ShowtimeSeat::whereKey($expired)->update(['status' => 'held', 'held_until' => now()->subMinute(), 'price_override' => 1]);
    ShowtimeSeat::whereKey($active)->update(['status' => 'held', 'held_until' => now()->addMinute()]);

    expect(holds()->releaseExpired())->toBe(1)
        ->and(ShowtimeSeat::find($expired)->status)->toBe('available')
        ->and(ShowtimeSeat::find($expired)->price_override)->toBeNull()
        ->and(ShowtimeSeat::find($active)->status)->toBe('held');
});
