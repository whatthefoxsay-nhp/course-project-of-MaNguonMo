<?php

use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\RoomSeatGenerator;
use App\Services\ShowtimeSeatGenerator;
use App\Support\SeatMap;

function zonedShowtime(string $preset): Showtime
{
    $room = Room::factory()->create(['layout_preset' => $preset]);
    app(RoomSeatGenerator::class)->generate($room, $preset);
    $showtime = Showtime::factory()->for($room)->for(Event::factory()->create(['is_seated' => true]))->create(['base_price' => 200000]);
    app(ShowtimeSeatGenerator::class)->generate($showtime);

    return $showtime;
}

test('seat map groups zones by level with sold and held states', function () {
    $showtime = zonedShowtime('mega_concert');
    $seatIn = fn (string $row) => $showtime->showtimeSeats()->whereHas('seat', fn ($q) => $q->where('row_label', $row))->orderBy('id')->first();
    $seatIn('SVIP-A')->update(['status' => 'booked']);
    $seatIn('FL-1')->update(['status' => 'held', 'held_by_user_id' => createCustomer()->id, 'held_until' => now()->addMinutes(5)]);

    $map = SeatMap::forShowtime($showtime);
    $zone = fn (string $code) => collect($map['levels'])->flatten(1)->firstWhere('code', $code);

    expect(array_keys($map['levels']))->toBe([1, 2, 3, 4])
        ->and(collect($map['levels'][1])->pluck('code')->all())->toBe(['GA-L', 'SVIP', 'GA-R'])
        ->and(collect($map['levels'][3])->pluck('code')->all())->toBe(['KD-C', 'KD-A', 'KD-D'])
        ->and($zone('SVIP')['rows'][0]['seats'][0]['status'])->toBe('booked')
        ->and($zone('VIP')['rows'][0]['seats'][0]['status'])->toBe('held')
        ->and($zone('SVIP')['available'])->toBe(23)
        ->and($zone('SVIP')['price'])->toBe(350000)
        ->and($zone('GA-L')['rows'])->toBe([])
        ->and($zone('GA-L')['available'])->toBe(40)
        ->and($map['is_seated'])->toBeTrue();
});

test('aisles show up as gaps in seat columns', function () {
    $map = SeatMap::forShowtime(zonedShowtime('theater_hall'));
    $stalls = $map['levels'][1][0];
    $firstRow = $stalls['rows'][0];

    expect($stalls['max_col'])->toBe(17)
        ->and($firstRow['label'])->toBe('ST-A')
        ->and(collect($firstRow['seats'])->pluck('col')->contains(9))->toBeFalse()
        ->and($firstRow['seats'][0]['code'])->toBe('ST-A-01');
});

test('zone cards list every zone with its price and free places', function () {
    $cards = SeatMap::zoneCards(zonedShowtime('open_space'));

    expect(collect($cards)->pluck('code')->all())->toBe(['VIPX', 'GEN'])
        ->and($cards[1]['available'])->toBe(300)
        ->and($cards[0]['price'])->toBe(350000)
        ->and($cards[0])->not->toHaveKey('rows');
});
