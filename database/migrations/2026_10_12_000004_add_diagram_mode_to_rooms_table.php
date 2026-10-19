<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('diagram_mode', 16)->default('seats')->after('layout_preset');
        });

        if (Schema::hasTable('room_zones')) {
            DB::table('rooms')
                ->whereIn('layout_preset', ['outdoor_concert', 'mega_concert', 'sayhi_concert', 'sayhi_concert_d2'])
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('room_zones')->whereColumn('room_zones.room_id', 'rooms.id'))
                ->update(['diagram_mode' => 'zones']);
        }
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('diagram_mode');
        });
    }
};
