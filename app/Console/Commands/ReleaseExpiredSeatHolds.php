<?php

namespace App\Console\Commands;

use App\Services\SeatHoldService;
use Illuminate\Console\Command;

class ReleaseExpiredSeatHolds extends Command
{
    protected $signature = 'seats:release-expired';

    protected $description = 'Trả các ghế giữ quá hạn về trạng thái còn trống';

    public function handle(SeatHoldService $holds): int
    {
        $count = $holds->releaseExpired();
        $this->info("Đã nhả {$count} ghế hết hạn giữ.");

        return self::SUCCESS;
    }
}
