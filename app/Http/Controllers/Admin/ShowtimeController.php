<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShowtimeRequest;
use App\Models\Event;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\ShowtimeSeatGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShowtimeController extends Controller
{
    public function index(Request $request): View
    {
        // Giữ eager-load qua alias `movie` vì view index đang đọc $showtime->movie.
        $query = Showtime::with(['movie.category', 'room', 'showtimeSeats']);

        // Tham số giữ tên `movie_id` vì view filter hiện có đang dùng tên này.
        if ($request->filled('movie_id')) {
            $query->where('event_id', $request->input('movie_id'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->input('room_id'));
        }

        $showtimes = $query->orderBy('start_time')->paginate(10)->withQueryString();
        $events = Event::orderBy('title')->get();
        $rooms = Room::orderBy('name')->get();

        return view('admin.showtimes.index', compact('showtimes', 'events', 'rooms'));
    }

    public function create(): View
    {
        return view('admin.showtimes.form', $this->formData(new Showtime(['base_price' => 200000])));
    }

    public function store(ShowtimeRequest $request, ShowtimeSeatGenerator $seats): RedirectResponse
    {
        $showtime = DB::transaction(function () use ($request, $seats) {
            $showtime = Showtime::create($request->validated());
            $seats->generate($showtime);

            return $showtime;
        });

        return redirect()->route('admin.showtimes.index')
            ->with('success', 'Đã tạo suất diễn với '.$showtime->showtimeSeats()->count().' chỗ.');
    }

    public function edit(Showtime $showtime): View
    {
        return view('admin.showtimes.form', $this->formData($showtime));
    }

    public function update(ShowtimeRequest $request, Showtime $showtime, ShowtimeSeatGenerator $seats): RedirectResponse
    {
        $roomChanged = (int) $request->validated('room_id') !== $showtime->room_id;

        if ($roomChanged && $showtime->showtimeSeats()->whereIn('status', ['held', 'booked'])->exists()) {
            return back()->withInput()->with('error', 'Suất diễn đã có ghế được giữ hoặc đã bán, không thể đổi khán phòng.');
        }

        DB::transaction(function () use ($request, $showtime, $seats, $roomChanged) {
            $showtime->update($request->validated());

            if ($roomChanged) {
                $seats->regenerate($showtime);
            }
        });

        return redirect()->route('admin.showtimes.index')->with('success', 'Đã cập nhật suất diễn.');
    }

    public function destroy(Showtime $showtime): RedirectResponse
    {
        if ($showtime->showtimeSeats()->where('status', 'booked')->exists()) {
            return back()->with('error', 'Suất diễn đã có vé bán ra, không thể xóa.');
        }

        $showtime->delete();

        return redirect()->route('admin.showtimes.index')->with('success', 'Đã xóa suất diễn.');
    }

    private function formData(Showtime $showtime): array
    {
        return [
            'showtime' => $showtime,
            'events' => Event::orderBy('title')->get(),
            'rooms' => Room::withCount('seats')->orderBy('name')->get(),
        ];
    }
}
