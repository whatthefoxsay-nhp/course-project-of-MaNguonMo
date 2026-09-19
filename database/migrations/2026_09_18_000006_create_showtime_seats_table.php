<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('showtime_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showtime_id')->constrained('showtimes')->onDelete('cascade');
            $table->foreignId('seat_id')->constrained('seats')->onDelete('cascade');
            $table->enum('status', ['available', 'held', 'booked'])->default('available');
            $table->foreignId('held_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('held_until')->nullable();
            $table->unsignedInteger('price_override')->nullable();
            $table->timestamps();

            // Each physical seat has exactly one state per showtime
            $table->unique(['showtime_id', 'seat_id']);
            $table->index(['status', 'held_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showtime_seats');
    }
};
