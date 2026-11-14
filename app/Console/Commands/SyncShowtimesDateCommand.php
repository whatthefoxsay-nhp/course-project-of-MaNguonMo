<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Showtime;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncShowtimesDateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:sync-dates 
                            {--days=7 : Số ngày dời về sau tính từ hôm nay}
                            {--event= : ID hoặc slug của sự kiện cần dời (bỏ trống để dời toàn bộ)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động dời lịch các suất diễn đã quá hạn sang tương lai để tiếp tục mở bán';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $eventIdentifier = $this->option('event');

        $eventsQuery = Event::query();

        if ($eventIdentifier) {
            $eventsQuery->where(function ($query) use ($eventIdentifier) {
                if (is_numeric($eventIdentifier)) {
                    $query->where('id', $eventIdentifier);
                } else {
                    $query->where('slug', $eventIdentifier);
                }
            });
        }

        $events = $eventsQuery->with('showtimes')->get();

        if ($events->isEmpty()) {
            $this->warn('Không tìm thấy sự kiện nào phù hợp.');
            return self::FAILURE;
        }

        $baseFutureDate = Carbon::today()->addDays($days);
        $totalSynced = 0;

        foreach ($events as $event) {
            $showtimes = $event->showtimes()->orderBy('start_time')->get();

            if ($showtimes->isEmpty()) {
                continue;
            }

            // Cập nhật ngày mở bán của sự kiện sang ngày tương lai nếu đang ở quá khứ
            if (! $event->release_date || $event->release_date->isPast()) {
                $event->release_date = Carbon::today();
                $event->save();
            }

            $firstShowtime = $showtimes->first();
            $firstOriginalDate = $firstShowtime->start_time->copy()->startOfDay();

            foreach ($showtimes as $index => $showtime) {
                // Chỉ dời nếu suất diễn đã quá hạn hoặc người dùng chỉ định dời
                if ($showtime->start_time <= now()) {
                    $originalStart = $showtime->start_time;
                    $originalEnd = $showtime->end_time;

                    // Tính offset ngày so với suất đầu
                    $dayOffset = $firstOriginalDate->diffInDays($originalStart->copy()->startOfDay(), false);
                    if ($dayOffset < 0) {
                        $dayOffset = 0;
                    }

                    $newStart = $baseFutureDate->copy()->addDays($dayOffset)
                        ->setTime($originalStart->hour, $originalStart->minute, $originalStart->second);

                    $durationSeconds = $originalEnd 
                        ? $originalStart->diffInSeconds($originalEnd) 
                        : (($event->duration_minutes ?: 120) * 60);

                    $newEnd = $newStart->copy()->addSeconds($durationSeconds);

                    $showtime->update([
                        'start_time' => $newStart,
                        'end_time' => $newEnd,
                    ]);

                    $totalSynced++;
                    $this->info("✓ [{$event->title}] Đã dời suất #ST-{$showtime->id} sang: {$newStart->format('d/m/Y H:i')}");
                }
            }
        }

        $this->info("\nHoàn tất! Đã cập nhật thành công {$totalSynced} suất diễn sang tương lai.");
        return self::SUCCESS;
    }
}

