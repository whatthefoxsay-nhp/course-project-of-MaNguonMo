<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->foreignId('showtime_seat_id')->nullable()->change();
            $table->foreignId('zone_ticket_id')->nullable()->after('showtime_seat_id')->constrained('zone_tickets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_ticket_id');
            $table->foreignId('showtime_seat_id')->nullable(false)->change();
        });
    }
};
