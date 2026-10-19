<?php

use App\Models\BookingItem;
use App\Models\Category;
use App\Models\Event;
use App\Models\Room;

beforeEach(fn () => $this->seed());

test('home stats come from the database instead of hard-coded numbers', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('50K+')
        ->assertDontSee('4.9/5')
        ->assertViewHas('stats', fn (array $stats) => $stats['venues'] === Room::count()
            && $stats['tickets_sold'] === BookingItem::whereHas('booking', fn ($q) => $q->where('status', 'confirmed'))->count()
            && $stats['events_on_sale'] > 0);
});

test('event card shows real venue and sale status', function () {
    Event::factory()->create([
        'title' => 'Sự kiện chưa có lịch',
        'category_id' => Category::first()->id,
        'status' => 'published',
        'release_date' => now()->addYear(),
    ]);

    $this->get(route('events.index', ['q' => 'chưa có lịch']))
        ->assertOk()
        ->assertSee('Chưa có suất diễn')
        ->assertDontSee('Khán phòng VIP')
        ->assertDontSee('Show Diễn 2026');

    $this->get(route('events.index', ['q' => 'Anh Trai Vượt Ngàn']))
        ->assertOk()
        ->assertSee('Đang mở bán')
        ->assertSee('Sân Vận Động Quân Khu 7', false);
});

test('home category tab value is escaped as a javascript literal', function () {
    $this->get(route('home', ['category' => "x';alert(1);'"]))
        ->assertOk()
        ->assertDontSee("currentTab: 'x';alert(1)", false);
});
