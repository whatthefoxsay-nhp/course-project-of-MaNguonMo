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
            ],
        );
    }

    public function attachments(): array
    {
        $this->loadBooking();
        $documents = app(TicketPdfService::class);
        $receipt = $documents->generateReceipt($this->booking);
        $ticket = $documents->generate($this->booking);

        return [
            Attachment::fromData(fn () => $receipt, "bien-lai-{$this->booking->booking_code}.pdf")
                ->withMime('application/pdf'),
            Attachment::fromData(fn () => $ticket, "ve-dien-tu-{$this->booking->booking_code}.pdf")
                ->withMime('application/pdf'),
        ];
    }

    private function loadBooking(): void
    {
        $this->booking->loadMissing([
            'items.showtimeSeat.seat.zone',
            'items.showtimeSeat.showtime.event',
            'items.showtimeSeat.showtime.room',
        ]);
    }
}
