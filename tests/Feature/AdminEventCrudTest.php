<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

function eventPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Đêm Nhạc Trịnh',
        'category_id' => Category::factory()->create()->id,
        'status' => 'published',
        'is_seated' => '1',
        'duration_minutes' => 150,
        'release_date' => now()->addWeek()->toDateString(),
        'description' => 'Mô tả',
        'details' => ['venue_name' => 'Nhà hát Hòa Bình', 'venue_address' => '240 3/2, Q.10'],
        'lineup_text' => "Hà Anh Tuấn | Ca sĩ | Khách mời | Headliner\nPhương Linh | Ca sĩ",
        'timeline_text' => "18:00 | Check-in | Đổi vé\n19:30 | Mở màn",
    ], $overrides);
}

test('admin creates an event with a poster and structured details', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), eventPayload([
            'poster' => UploadedFile::fake()->create('poster.jpg', 100, 'image/jpeg'),
        ]))
        ->assertRedirect(route('admin.events.index'))
        ->assertSessionHas('success');

    $event = Event::firstWhere('title', 'Đêm Nhạc Trịnh');

    expect($event->slug)->toBe('dem-nhac-trinh')
        ->and($event->is_seated)->toBeTrue()
        ->and($event->venue_name)->toBe('Nhà hát Hòa Bình')
        ->and($event->lineup)->toHaveCount(2)
        ->and($event->lineup[0])->toBe(['name' => 'Hà Anh Tuấn', 'role' => 'Ca sĩ', 'tag' => 'Khách mời', 'badge' => 'Headliner'])
        ->and($event->lineup[1]['badge'])->toBe('')
        ->and($event->timeline[1])->toBe(['time' => '19:30', 'title' => 'Mở màn', 'desc' => '']);

    Storage::disk('public')->assertExists($event->poster_path);
});

test('updating an event keeps detail keys the form does not edit', function () {
    $event = Event::factory()->create([
        'details' => ['organizers' => ['lead' => 'TicketBox'], 'venue_name' => 'Cũ'],
    ]);

    $this->actingAs(createAdmin())
        ->put(route('admin.events.update', $event), eventPayload(['title' => 'Tên mới']))
        ->assertRedirect(route('admin.events.index'));

    $event->refresh();
    expect($event->title)->toBe('Tên mới')
        ->and($event->venue_name)->toBe('Nhà hát Hòa Bình')
        ->and($event->organizers)->toBe(['lead' => 'TicketBox']);
});

test('poster must be an image', function () {
    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), eventPayload([
            'poster' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('poster');
});

test('admin deletes an event without sold tickets and its poster file', function () {
    Storage::disk('public')->put('posters/a.jpg', 'x');
    $event = Event::factory()->create(['poster_path' => 'posters/a.jpg']);

    $this->actingAs(createAdmin())
        ->delete(route('admin.events.destroy', $event))
        ->assertSessionHas('success');

    expect(Event::find($event->id))->toBeNull();
    Storage::disk('public')->assertMissing('posters/a.jpg');
});

test('an event with sold tickets cannot be deleted', function () {
    $room = Room::factory()->create();
    $seat = Seat::create(['room_id' => $room->id, 'row_label' => 'A', 'seat_number' => 1, 'type' => 'cat1_stand']);
    $showtime = Showtime::factory()->for($room)->create();
    ShowtimeSeat::create(['showtime_id' => $showtime->id, 'seat_id' => $seat->id, 'status' => 'booked']);

    $this->actingAs(createAdmin())
        ->delete(route('admin.events.destroy', $showtime->event))
        ->assertSessionHas('error');

    expect(Event::find($showtime->event_id))->not->toBeNull();
});

test('admin event pages render', function () {
    $event = Event::factory()->create();
    $admin = createAdmin();

    $this->actingAs($admin)->get(route('admin.events.index'))->assertOk()->assertSee($event->title);
    $this->actingAs($admin)->get(route('admin.events.create'))->assertOk();
    $this->actingAs($admin)->get(route('admin.events.edit', $event))->assertOk()->assertSee($event->title);
});
