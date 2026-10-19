<?php

use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;

function showtimeSeatOfType(string $type, array $attributes = []): ShowtimeSeat
{
    $showtime = Showtime::factory()->create(['base_price' => 200000]);
    $seat = Seat::create(['room_id' => $showtime->room_id, 'row_label' => 'A', 'seat_number' => 1, 'type' => $type]);

    return ShowtimeSeat::create(array_merge(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'available'], $attributes));
}

test('effective price follows the ticket tier of the seat', function () {
    expect(showtimeSeatOfType('vip_gold')->effective_price)->toBe(280000)
        ->and(showtimeSeatOfType('skybox_suite')->effective_price)->toBe(600000)
        ->and(showtimeSeatOfType('normal')->effective_price)->toBe(200000);
});

test('a price override wins over the tier price', function () {
    expect(showtimeSeatOfType('vip_gold', ['price_override' => 123000])->effective_price)->toBe(123000);
});

test('an expired hold is shown as available', function () {
    $expired = showtimeSeatOfType('cat1_stand', ['status' => 'held', 'held_until' => now()->subMinute()]);
    $active = showtimeSeatOfType('cat1_stand', ['status' => 'held', 'held_until' => now()->addMinutes(5)]);

    expect($expired->publicStatus())->toBe('available')
        ->and($active->publicStatus())->toBe('held');
});

test('vip seat types are recognised', function () {
    expect((new Seat(['type' => 'svip_diamond']))->isVip())->toBeTrue()
        ->and((new Seat(['type' => 'cat2_wings']))->isVip())->toBeFalse();
});
