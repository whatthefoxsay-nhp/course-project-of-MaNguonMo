<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Services\GoogleDriveService;
use App\Services\TicketPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class BookingController extends Controller
{
    public function history(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with([
                'items.showtimeSeat.seat.zone',
                'items.showtimeSeat.showtime.room',
                'items.showtimeSeat.showtime.event.category',
            ])
            ->latest('id')
            ->paginate(5);

        $bookings->through(function (Booking $booking) {
            // Mỗi đơn luôn thuộc 1 suất (SeatHoldService chỉ cho giữ ghế của 1 suất tại 1 thời điểm).
            $showtime = $booking->items->first()?->showtimeSeat?->showtime;

            return (object) [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'movie' => $showtime?->event,
                'showtime' => $showtime,
                'seats' => $booking->items->map(function ($item) {
                    $zone = $item->showtimeSeat?->seat?->zone;
                    if ($zone && $zone->isStanding()) {
                        return $zone->name . ' (#' . ($item->showtimeSeat?->seat?->seat_number ?? $item->id) . ')';
                    }
                    return $item->showtimeSeat?->seat?->full_code ?? '';
                })->filter()->all(),
                'total_price' => $booking->total_price,
                'payment_method' => $booking->payment_method,
                'status' => $booking->status,
                'created_at' => $booking->created_at,
            ];
        });

        return view('bookings.history', ['bookings' => $bookings]);
    }

    public function ticket(Request $request, Booking $booking): View
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'confirmed', 403, 'Đơn hàng chưa thanh toán hoặc đã bị hủy.');

        $booking->load([
            'items.showtimeSeat.seat.zone',
            'items.showtimeSeat.showtime.room',
            'items.showtimeSeat.showtime.event.category',
        ]);

        return view('bookings.ticket', [
            'booking' => $booking,
        ]);
    }

    public function ticketPdf(Request $request, Booking $booking, TicketPdfService $pdfService): Response
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'confirmed', 403, 'Đơn hàng chưa thanh toán hoặc đã bị hủy.');

        $pdfContent = $pdfService->generate($booking);
        $filename = "ve-dien-tu-{$booking->booking_code}.pdf";

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function sendTicketEmail(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'confirmed', 403, 'Đơn hàng chưa thanh toán hoặc đã bị hủy.');

        $email = $request->input('email') ?? $request->user()->email;

        try {
            Mail::to($email)->send(new BookingConfirmationMail($booking));

            return response()->json([
                'success' => true,
                'message' => "Đã gửi vé điện tử đến địa chỉ {$email} thành công!",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email lúc này. Vui lòng kiểm tra lại cấu hình gửi mail hoặc thử lại sau.',
            ], 500);
        }
    }

    public function syncTicketDrive(Request $request, Booking $booking, TicketPdfService $pdfService, GoogleDriveService $driveService): JsonResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'confirmed', 403, 'Đơn hàng chưa thanh toán hoặc đã bị hủy.');

        try {
            $pdfContent = $pdfService->generate($booking);
            $filename = "ve-dien-tu-{$booking->booking_code}.pdf";
            $driveUrl = $driveService->uploadContent($pdfContent, $filename, 'application/pdf', 'tickets');

            if (! $driveUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dịch vụ lưu trữ Google Drive hiện đang tạm ngưng hoặc chưa được cấu hình.',
                ], 503);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã đồng bộ bản sao vé điện tử lên Google Drive thành công!',
                'drive_url' => $driveUrl,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối khi đồng bộ lên Google Drive. Vui lòng thử lại sau.',
            ], 500);
        }
    }
}
