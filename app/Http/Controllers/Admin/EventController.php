<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Category;
use App\Models\Event;
use App\Models\ShowtimeSeat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $query = Event::with('category')->withCount('showtimes');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $events = $query->latest()->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.events.index', compact('events', 'categories'));
    }

    public function create(): View
    {
        return view('admin.events.form', [
            'event' => new Event(['status' => 'draft', 'is_seated' => true]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $event = new Event($request->eventAttributes());
        $event->details = $request->mergedDetails([]);

        if ($request->hasFile('poster')) {
            $event->poster_path = $request->file('poster')->store('posters', 'public');
        }

        $event->save();

        return redirect()->route('admin.events.index')
            ->with('success', "Đã tạo sự kiện [{$event->title}].");
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', [
            'event' => $event,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $event->fill($request->eventAttributes());
        $event->details = $request->mergedDetails($event->details ?? []);

        if ($request->hasFile('poster')) {
            $this->deleteStoredPoster($event);
            $event->poster_path = $request->file('poster')->store('posters', 'public');
        }

        $event->save();

        return redirect()->route('admin.events.index')
            ->with('success', "Đã cập nhật sự kiện [{$event->title}].");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $hasSoldTickets = ShowtimeSeat::whereIn('showtime_id', $event->showtimes()->select('id'))
            ->where('status', 'booked')
            ->exists();

        if ($hasSoldTickets) {
            return back()->with('error', 'Sự kiện đã có vé bán ra, không thể xóa. Hãy chuyển trạng thái sang "Lưu trữ".');
        }

        $this->deleteStoredPoster($event);
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Đã xóa sự kiện.');
    }

    /** Chỉ xóa file đã upload lên disk public, bỏ qua poster là URL ngoài (dữ liệu seed). */
    private function deleteStoredPoster(Event $event): void
    {
        if ($event->poster_path && ! Str::startsWith($event->poster_path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($event->poster_path);
        }
    }
}
