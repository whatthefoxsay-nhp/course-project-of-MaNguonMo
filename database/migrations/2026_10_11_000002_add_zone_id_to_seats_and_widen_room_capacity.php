<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            // nullable: phòng tạo theo preset cũ chưa có khu.
            $table->foreignId('zone_id')->nullable()->after('room_id')->constrained('room_zones')->cascadeOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table) {
            // smallint (tối đa 65.535) không đủ cho sân vận động lớn.
            $table->unsignedInteger('capacity')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedSmallInteger('capacity')->default(50)->change();
        });

        Schema::table('seats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_id');
        });
    }
};
