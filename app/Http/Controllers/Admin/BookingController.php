<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Booking::with(['user', 'items.showtimeSeat.showtime.movie', 'items.showtimeSeat.seat', 'items.showtimeSeat.showtime.room']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $bookings = $query->latest()->paginate(10)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Request $request, Booking $booking): View|JsonResponse
    {
        $booking->load([
            'user',
            'items.showtimeSeat.seat',
            'items.showtimeSeat.showtime.movie.category',
            'items.showtimeSeat.showtime.room',
        ]);

        if ($request->wantsJson()) {
            return response()->json($booking);
        }

        return view('admin.bookings.show', compact('booking'));
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,cancelled,pending',
        ]);

        $newStatus = $validated['status'];

        DB::transaction(function () use ($booking, $newStatus) {
            $booking->update(['status' => $newStatus]);

            if ($newStatus === 'cancelled') {
                foreach ($booking->items as $item) {
                    if ($item->showtimeSeat) {
                        $item->showtimeSeat->update([
                            'status' => 'available',
                            'held_by_user_id' => null,
                            'held_until' => null,
                        ]);
                    }
                }
            } elseif ($newStatus === 'confirmed') {
                foreach ($booking->items as $item) {
                    if ($item->showtimeSeat) {
                        $item->showtimeSeat->update([
                            'status' => 'booked',
                            'held_by_user_id' => null,
                            'held_until' => null,
                        ]);
                    }
                }
            }
        });

        $message = match ($newStatus) {
            'cancelled' => "Đã hủy đơn vé [{$booking->booking_code}] và giải phóng toàn bộ ghế liên quan.",
            'confirmed' => "Đã xác nhận thanh toán đơn vé [{$booking->booking_code}].",
            default => "Đã chuyển trạng thái đơn vé [{$booking->booking_code}] sang đang giữ chỗ.",
        };

        return back()->with('success', $message);
    }
}
