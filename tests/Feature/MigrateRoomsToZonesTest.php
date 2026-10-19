<?php

use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;

function legacyRoomWithGaSeats(): Room
{
    $room = Room::factory()->create(['capacity' => 99, 'layout_preset' => 'mega_concert']);
    foreach ([['A1', 'cat1_stand'], ['A1', 'cat1_stand'], ['GA', 'standing_pit'], ['GA', 'standing_pit']] as [$row, $type]) {
        Seat::create([
            'room_id' => $room->id,
            'row_label' => $row,
            'seat_number' => $room->seats()->where('row_label', $row)->count() + 1,
            'type' => $type,
        ]);
    }
    $event = Event::factory()->create();
    $showtime = Showtime::factory()->for($room)->for($event)->create();
    $gaSeats = $room->seats()->where('type', 'standing_pit')->get();
    ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $gaSeats[0]->id, 'status' => 'held', 'held_until' => now()->addMinutes(5)]);
    ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $gaSeats[1]->id, 'status' => 'available']);

    return $room;
}

test('dry run reports changes without persisting the legacy room migration', function () {
    $room = legacyRoomWithGaSeats();

    $this->artisan('rooms:migrate-zones --dry-run')
        ->expectsOutputToContain('Chế độ xem trước')
        ->assertSuccessful();

    expect($room->zones()->count())->toBe(0)
        ->and($room->seats()->count())->toBe(4)
        ->and($room->fresh()->capacity)->toBe(99);
});

test('migration creates seated and standing zones and keeps active legacy GA holds', function () {
    $room = legacyRoomWithGaSeats();

    $this->artisan('rooms:migrate-zones')->assertSuccessful();

    $room->refresh();
    $standing = $room->zones()->where('kind', 'standing')->firstOrFail();
    expect($room->isZoned())->toBeTrue()
        ->and($room->capacity)->toBe(4)
        ->and($room->seats()->count())->toBe(3)
        ->and($standing->capacity)->toBe(2)
        ->and($standing->seats()->count())->toBe(1)
        ->and($room->zones()->where('kind', 'seated')->count())->toBe(1);
});
