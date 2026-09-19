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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['event', 'concert', 'workshop', 'movie'])->default('event');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->date('release_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
