<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_zones', function (Blueprint $table) {
            $table->string('ticket_tier', 50)->nullable()->after('seat_type');
            $table->json('meta')->nullable()->after('layout');
        });
    }

    public function down(): void
    {
        Schema::table('room_zones', function (Blueprint $table) {
            $table->dropColumn(['ticket_tier', 'meta']);
        });
    }
};
