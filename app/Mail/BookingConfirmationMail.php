<?php

namespace App\Mail;

use App\Models\Booking;
use App\Services\TicketPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $this->loadBooking();
        $eventTitle = $this->booking->items->first()?->showtimeSeat?->showtime?->event?->title ?? 'đặt vé';

        return new Envelope(subject: "🎫 Xác nhận vé — {$this->booking->booking_code} | {$eventTitle}");
    }

    public function content(): Content
    {
        $this->loadBooking();

        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'booking' => $this->booking,
                'items' => $this->booking->items,
                'user' => $this->booking->user,
            ],
        );
    }

    public function attachments(): array
    {
        $this->loadBooking();
        $pdf = app(TicketPdfService::class)->generate($this->booking);

        return [
            Attachment::fromData(fn () => $pdf, "ticket-{$this->booking->booking_code}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    private function loadBooking(): void
    {
        $this->booking->loadMissing([
            'user',
            'items.showtimeSeat.seat.zone',
            'items.showtimeSeat.showtime.event',
            'items.showtimeSeat.showtime.room',
        ]);
    }
}
