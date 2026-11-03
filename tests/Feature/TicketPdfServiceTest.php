<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\TicketPdfService;

it('generates a PDF ticket with booking details and a QR code', function () {
    $user = User::factory()->create(['name' => 'Nguyễn An']);
    $event = Event::factory()->create(['title' => 'Đêm nhạc mùa thu']);
    $room = Room::factory()->create(['name' => 'Nhà hát Hòa Bình']);
    $showtime = Showtime::factory()->for($event)->for($room)->create([
        'start_time' => now()->addWeek(),
    ]);
    $seat = Seat::create([
        'room_id' => $room->id,
        'row_label' => 'A',
        'seat_number' => 12,
        'type' => 'normal',
    ]);
    $showtimeSeat = ShowtimeSeat::create([
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'status' => 'booked',
    ]);
    $booking = Booking::create([
        'user_id' => $user->id,
        'booking_code' => 'TBX-PDF12345',
        'total_price' => 350000,
        'status' => 'confirmed',
        'payment_method' => 'vietqr',
    ]);
    BookingItem::create([
        'booking_id' => $booking->id,
        'showtime_seat_id' => $showtimeSeat->id,
        'price' => 350000,
    ]);

    $html = view('tickets.pdf', ['booking' => $booking->fresh()])->render();
    $pdf = app(TicketPdfService::class)->generate($booking->fresh());

    expect($html)->toContain('TBX-PDF12345', 'Đêm nhạc mùa thu', 'Nhà hát Hòa Bình', 'A12', 'data:image/svg+xml;base64,')
        ->and($pdf)->toStartWith('%PDF-');
});
