<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showtime_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('room_zones')->cascadeOnDelete();
            $table->enum('status', ['held', 'booked']);
            $table->foreignId('held_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('held_until')->nullable();
            $table->unsignedInteger('price');
            $table->timestamps();

            $table->index(['showtime_id', 'zone_id', 'status']);
            $table->index(['status', 'held_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_tickets');
    }
};
