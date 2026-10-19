<?php

use App\Models\Booking;
use App\Models\User;
use App\Support\UpcomingShowtimes;

beforeEach(fn () => $this->seed());

test('upcoming showtimes only include the user\'s confirmed future bookings', function () {
    $demo = User::firstWhere('email', 'user@ticketbox.vn');

    $upcoming = UpcomingShowtimes::for($demo);

    expect($upcoming)->toHaveCount(1)
        ->and($upcoming->first()->event->slug)->toBe('hoa-nhac-giao-huong-saigon-philharmonic');

    Booking::where('user_id', $demo->id)->update(['status' => 'cancelled']);
    expect(UpcomingShowtimes::for($demo))->toBeEmpty();

    expect(UpcomingShowtimes::for(createCustomer()))->toBeEmpty()
        ->and(UpcomingShowtimes::for(null))->toBeEmpty();
});

test('notification bell lists the real upcoming showtime instead of placeholder data', function () {
    $demo = User::firstWhere('email', 'user@ticketbox.vn');

    $this->actingAs($demo)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Đêm Hòa Nhạc Giao Hưởng')
        ->assertDontSee('Live Concert Acoustic 2026');

    $this->actingAs(createCustomer())->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Bạn chưa có suất diễn nào sắp tới.');
});

test('guests do not see the notification bell', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Thông Báo Sự Kiện');
});
