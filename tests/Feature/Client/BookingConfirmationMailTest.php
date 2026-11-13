<?php

use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Event;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;

it('renders the receipt email and attaches separate receipt and ticket PDFs', function () {
    $user = User::factory()->create(['name' => 'Trần Bình']);
    $event = Event::factory()->create(['title' => 'Đêm nhạc xác nhận']);
    $room = Room::factory()->create(['name' => 'Nhà hát Thành phố']);
    $showtime = Showtime::factory()->for($event)->for($room)->create([
        'start_time' => now()->addWeek(),
    ]);
    $seat = Seat::create([
        'room_id' => $room->id,
        'row_label' => 'B',
        'seat_number' => 8,
        'type' => 'normal',
    ]);
    $showtimeSeat = ShowtimeSeat::create([
        'showtime_id' => $showtime->id,
        'seat_id' => $seat->id,
        'status' => 'booked',
    ]);
    $booking = Booking::create([
        'user_id' => $user->id,
        'booking_code' => 'TBX-MAIL1234',
        'total_price' => 425000,
        'status' => 'confirmed',
        'payment_method' => 'momo',
    ]);
    BookingItem::create([
        'booking_id' => $booking->id,
        'showtime_seat_id' => $showtimeSeat->id,
        'price' => 425000,
    ]);

    $mail = new BookingConfirmationMail($booking->fresh());
    $attachments = $mail->attachments();
    $pdfPrefixes = array_map(fn ($attachment) => $attachment->attachWith(
        fn ($path) => $path,
        fn ($data) => substr($data(), 0, 5),
    ), $attachments);
    $html = $mail->render();

    expect($mail->envelope()->subject)->toContain('TBX-MAIL1234', 'Đêm nhạc xác nhận')
        ->and($html)->toContain('Trần Bình', 'Nhà hát Thành phố', 'B8', '425.000₫', 'Vé đã đặt', 'Biên lai thanh toán')
        ->and(array_map(fn ($attachment) => $attachment->as, $attachments))->toBe([
            'bien-lai-TBX-MAIL1234.pdf',
            've-dien-tu-TBX-MAIL1234.pdf',
        ])
        ->and(array_map(fn ($attachment) => $attachment->mime, $attachments))->toBe(['application/pdf', 'application/pdf'])
        ->and($pdfPrefixes)->toBe(['%PDF-', '%PDF-']);
});
