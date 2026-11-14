<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Services\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config(['services.google_drive.enabled' => false]);
});

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
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('isEnabled')->once()->andReturn(true);
    $drive->shouldReceive('upload')->once()->with(Mockery::type(UploadedFile::class))
        ->andReturn('https://drive.google.com/uc?export=view&id=event_poster_123');
    $this->app->instance(GoogleDriveService::class, $drive);

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

    expect($event->poster_path)->toBe('https://drive.google.com/uc?export=view&id=event_poster_123');
});

test('admin can edit an event whose legacy detail rows are strings', function () {
    $event = Event::factory()->create([
        'details' => [
            'lineup' => ['Legacy lineup row'],
            'timeline' => ['18:00 | Legacy timeline row'],
        ],
    ]);

    $this->actingAs(createAdmin())
        ->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertSee('Legacy lineup row')
        ->assertSee('18:00 | Legacy timeline row');
});

test('admin can edit an event whose legacy lineup and timeline are scalar strings', function () {
    $event = Event::factory()->create([
        'details' => [
            'lineup' => 'SOOBIN',
            'timeline' => '19:00 mở màn',
        ],
    ]);

    $this->actingAs(createAdmin())
        ->get(route('admin.events.edit', $event))
        ->assertOk()
        ->assertSee('SOOBIN')
        ->assertSee('19:00 mở màn');
});

test('event poster URLs from Drive use the same-origin image route', function () {
    $event = Event::factory()->create([
        'poster_path' => 'https://drive.google.com/uc?id=drive_file_123&export=download',
    ]);

    expect($event->poster_url)->toBe(route('events.poster', $event).'?v=drive_file_123');
});

test('public event poster route streams Drive image bytes from the same origin', function () {
    $event = Event::factory()->create([
        'poster_path' => 'https://drive.google.com/uc?id=drive_file_123&export=download',
    ]);
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('extractFileId')->once()
        ->with('https://drive.google.com/uc?id=drive_file_123&export=download')
        ->andReturn('drive_file_123');
    $drive->shouldReceive('downloadImage')->once()->with('drive_file_123')
        ->andReturn(['content' => 'image bytes', 'mime_type' => 'image/jpeg']);
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->get(route('events.poster', $event))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertContent('image bytes');
});

test('public event poster route gracefully redirects to fallback image when Drive image is unavailable', function () {
    $event = Event::factory()->create([
        'slug' => 'demo-event-test',
        'poster_path' => 'https://drive.google.com/uc?id=broken_drive_file_id&export=download',
    ]);
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('extractFileId')->once()->andReturn('broken_drive_file_id');
    $drive->shouldReceive('downloadImage')->once()->andReturnNull();
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->get(route('events.poster', $event))
        ->assertRedirect('https://picsum.photos/seed/demo-event-test/480/720');
});

test('event poster URL resolves local storage disk when poster path is local', function () {
    Storage::disk('public')->put('posters/sample-test.jpg', 'local image content');

    $event = Event::factory()->create([
        'poster_path' => 'posters/sample-test.jpg',
    ]);

    expect($event->poster_url)->toContain('/storage/posters/sample-test.jpg');
});

test('admin event poster uploads to Google Drive when it is enabled', function () {
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('isEnabled')->once()->andReturn(true);
    $drive->shouldReceive('upload')->once()->with(Mockery::on(fn ($file) => $file instanceof UploadedFile))
        ->andReturn('https://drive.google.com/uc?export=view&id=drive_file_123');
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), eventPayload([
            'poster' => UploadedFile::fake()->create('poster.jpg', 100, 'image/jpeg'),
        ]))
        ->assertRedirect(route('admin.events.index'));

    $event = Event::firstWhere('title', 'Đêm Nhạc Trịnh');
    expect($event->poster_path)->toBe('https://drive.google.com/uc?export=view&id=drive_file_123');
    Storage::disk('public')->assertDirectoryEmpty('posters');
});

test('admin event poster upload fails instead of storing locally when Drive rejects it', function () {
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('isEnabled')->once()->andReturn(true);
    $drive->shouldReceive('upload')->once()->andReturnNull();
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->actingAs(createAdmin())
        ->post(route('admin.events.store'), eventPayload([
            'poster' => UploadedFile::fake()->create('poster.jpg', 100, 'image/jpeg'),
        ]))
        ->assertSessionHasErrors('poster');

    expect(Event::firstWhere('title', 'Đêm Nhạc Trịnh'))->toBeNull();
    Storage::disk('public')->assertDirectoryEmpty('posters');
});

test('updating an event poster deletes the old Drive file and stores the new one', function () {
    $event = Event::factory()->create([
        'poster_path' => 'https://drive.google.com/uc?export=view&id=old_drive_file',
    ]);
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('isEnabled')->once()->andReturn(true);
    $drive->shouldReceive('upload')->once()->with(Mockery::on(fn ($file) => $file instanceof UploadedFile))
        ->andReturn('https://drive.google.com/uc?export=view&id=new_drive_file');
    $drive->shouldReceive('extractFileId')->once()->with('https://drive.google.com/uc?export=view&id=old_drive_file')
        ->andReturn('old_drive_file');
    $drive->shouldReceive('delete')->once()->with('old_drive_file')->andReturn(true);
    $this->app->instance(GoogleDriveService::class, $drive);

    $this->actingAs(createAdmin())
        ->put(route('admin.events.update', $event), eventPayload([
            'poster' => UploadedFile::fake()->create('new-poster.jpg', 100, 'image/jpeg'),
        ]))
        ->assertRedirect(route('admin.events.index'));

    expect($event->fresh()->poster_path)->toBe('https://drive.google.com/uc?export=view&id=new_drive_file');
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

test('updating event release date syncs its showtimes to the new date and re-enables ticket booking', function () {
    $admin = createAdmin();
    $room = Room::factory()->create();

    // Sự kiện và suất diễn cũ trong quá khứ (hết hạn)
    $pastDate = Carbon\Carbon::today()->subDays(15);
    $event = Event::factory()->create([
        'release_date' => $pastDate,
        'duration_minutes' => 180,
        'status' => 'published',
    ]);

    $showtime = Showtime::factory()->for($event)->for($room)->create([
        'start_time' => $pastDate->copy()->setTime(19, 0),
        'end_time' => $pastDate->copy()->setTime(22, 0),
    ]);

    // Trang ngoài ban đầu báo hết hạn / chưa có suất mở bán
    $this->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertSee('Hiện chưa có suất diễn mở bán cho sự kiện này');

    // Admin chỉnh lại ngày mở bán lùi về sau (thời điểm tương lai)
    $futureDate = Carbon\Carbon::today()->addDays(10);
    $response = $this->actingAs($admin)
        ->put(route('admin.events.update', $event), eventPayload([
            'title' => $event->title,
            'category_id' => $event->category_id,
            'release_date' => $futureDate->format('Y-m-d'),
            'sync_showtimes' => '1',
        ]));

    $response->assertRedirect(route('admin.events.index'));
    $response->assertSessionHas('success');

    // Kiểm tra database: ngày mở bán và ngày giờ suất diễn đã được tự động dời sang tương lai
    expect($event->fresh()->release_date->format('Y-m-d'))->toBe($futureDate->format('Y-m-d'));
    
    $freshShowtime = $showtime->fresh();
    expect($freshShowtime->start_time->format('Y-m-d H:i'))->toBe($futureDate->format('Y-m-d') . ' 19:00')
        ->and($freshShowtime->end_time->format('Y-m-d H:i'))->toBe($futureDate->format('Y-m-d') . ' 22:00')
        ->and($freshShowtime->start_time->isFuture())->toBeTrue();

    // Trang chi tiết sự kiện lập tức hiển thị lại suất diễn và mở bán vé bình thường
    $this->get(route('events.show', $event->slug))
        ->assertOk()
        ->assertDontSee('Hiện chưa có suất diễn mở bán cho sự kiện này')
        ->assertSee('19:00');
});

