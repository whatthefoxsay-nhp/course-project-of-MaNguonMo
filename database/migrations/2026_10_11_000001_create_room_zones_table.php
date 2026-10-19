<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Khu vực của địa điểm: khu ghế ngồi (lưới hàng × ghế) hoặc khu đứng (chỉ có sức chứa).
     * Sức chứa của phòng = tổng sức chứa các khu.
     */
    public function up(): void
    {
        Schema::create('room_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('code', 6);
            $table->string('name', 100);
            $table->string('kind', 16); // seated | standing
            $table->string('seat_type', 50);
            $table->unsignedInteger('capacity')->default(0);
            $table->json('layout')->nullable(); // seated: [{row, seats: [{number, state}]}]
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['room_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_zones');
    }
};
