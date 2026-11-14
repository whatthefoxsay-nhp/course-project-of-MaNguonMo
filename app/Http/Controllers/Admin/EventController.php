<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Category;
use App\Models\Event;
use App\Models\ShowtimeSeat;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    public function store(EventRequest $request, GoogleDriveService $googleDrive): RedirectResponse
    {
        $event = new Event($request->eventAttributes());
        $event->details = $request->mergedDetails([]);

        if ($request->hasFile('poster')) {
            $event->poster_path = $this->storePoster($request->file('poster'), $googleDrive);
        }

        $event->save();

        return redirect()->route('admin.events.index')
            ->with('success', "Đã tạo sự kiện [{$event->title}].");
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', [
            'event' => $event->load(['showtimes.room', 'showtimes.showtimeSeats']),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(EventRequest $request, Event $event, GoogleDriveService $googleDrive): RedirectResponse
    {
        $oldReleaseDate = $event->release_date;

        $event->fill($request->eventAttributes());
        $event->details = $request->mergedDetails($event->details ?? []);
        $oldPosterPath = null;

        if ($request->hasFile('poster')) {
            $poster = $request->file('poster');
            $newPosterPath = $this->storePoster($poster, $googleDrive);
            $oldPosterPath = $event->poster_path;
            $event->poster_path = $newPosterPath;
        }

        $event->save();

        if ($oldPosterPath !== null) {
            $this->deletePosterPath($oldPosterPath, $googleDrive);
        }

        // Tự động đồng bộ / dời ngày các suất diễn nếu ngày mở bán thay đổi hoặc có suất diễn quá hạn
        $syncedCount = 0;
        $newReleaseDate = $event->release_date;
        $syncShowtimes = $request->boolean('sync_showtimes', true);

        if ($syncShowtimes && $newReleaseDate && $event->showtimes()->exists()) {
            $showtimes = $event->showtimes()->orderBy('start_time')->get();
            $firstShowtime = $showtimes->first();

            if ($firstShowtime) {
                $firstOriginalDate = $firstShowtime->start_time->copy()->startOfDay();

                foreach ($showtimes as $index => $showtime) {
                    $originalStart = $showtime->start_time;
                    $originalEnd = $showtime->end_time;

                    // Tính khoảng cách ngày so với suất đầu tiên để bảo toàn khoảng cách giữa các đêm diễn
                    $dayOffset = $firstOriginalDate->diffInDays($originalStart->copy()->startOfDay(), false);
                    if ($dayOffset < 0) {
                        $dayOffset = 0;
                    }

                    $newStart = $newReleaseDate->copy()->addDays($dayOffset)
                        ->setTime($originalStart->hour, $originalStart->minute, $originalStart->second);

                    $durationSeconds = $originalEnd ? $originalStart->diffInSeconds($originalEnd) : (($event->duration_minutes ?: 120) * 60);
                    $newEnd = $newStart->copy()->addSeconds($durationSeconds);

                    $showtime->update([
                        'start_time' => $newStart,
                        'end_time' => $newEnd,
                    ]);
                    $syncedCount++;
                }
            }
        }

        $message = $syncedCount > 0
            ? "Đã cập nhật sự kiện [{$event->title}] và tự động dời ngày {$syncedCount} suất diễn theo ngày mở bán mới ({$newReleaseDate->format('d/m/Y')})."
            : "Đã cập nhật sự kiện [{$event->title}].";

        return redirect()->route('admin.events.index')->with('success', $message);
    }

    public function destroy(Event $event, GoogleDriveService $googleDrive): RedirectResponse
    {
        $hasActiveSeats = ShowtimeSeat::whereIn('showtime_id', $event->showtimes()->select('id'))
            ->whereIn('status', ['held', 'booked'])
            ->exists();

        if ($hasActiveSeats) {
            return back()->with('error', 'Sự kiện đã có vé bán ra hoặc đang giữ chỗ, không thể xóa. Hãy chuyển trạng thái sang "Lưu trữ".');
        }

        $this->deleteStoredPoster($event, $googleDrive);
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Đã xóa sự kiện.');
    }

    /** Xóa poster Drive cũ hoặc local legacy file sau khi ảnh mới đã upload thành công. */
    private function storePoster(UploadedFile $poster, GoogleDriveService $googleDrive): string
    {
        if ($googleDrive->isEnabled()) {
            $url = $googleDrive->upload($poster);
            if ($url === null) {
                throw ValidationException::withMessages([
                    'poster' => 'Không tải được poster lên Google Drive. Vui lòng thử lại.',
                ]);
            }

            return $url;
        }

        return $poster->store('posters', 'public');
    }

    private function deleteStoredPoster(Event $event, GoogleDriveService $googleDrive): void
    {
        $this->deletePosterPath($event->poster_path, $googleDrive);
    }

    private function deletePosterPath(?string $posterPath, GoogleDriveService $googleDrive): void
    {
        if (! $posterPath) {
            return;
        }

        if (Str::startsWith($posterPath, ['http://', 'https://'])) {
            $fileId = $googleDrive->extractFileId($posterPath);
            if ($fileId !== null) {
                $googleDrive->delete($fileId);
            }

            return;
        }

        Storage::disk('public')->delete($posterPath);
    }
}
