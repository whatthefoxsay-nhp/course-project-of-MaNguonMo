<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // true: khách chọn ghế trên sơ đồ; false: khách chọn hạng vé + số lượng, server tự gán ghế
            $table->boolean('is_seated')->default(true)->after('status');
            // Thông tin mô tả: venue_name, venue_address, lineup[], timeline[], organizers{}, entry_policy{}...
            $table->json('details')->nullable()->after('is_seated');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['is_seated', 'details']);
        });
    }
};
