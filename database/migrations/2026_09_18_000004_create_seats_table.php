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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->string('row_label', 4); // A, B, C...
            $table->unsignedSmallInteger('seat_number'); // 1, 2, 3...
            $table->enum('type', ['normal', 'vip'])->default('normal');
            $table->timestamps();

            // Unique combination of row & number per room
            $table->unique(['room_id', 'row_label', 'seat_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
