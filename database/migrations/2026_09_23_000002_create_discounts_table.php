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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('title');
            $table->string('discount_type', 20)->default('percentage'); // percentage | fixed
            $table->unsignedInteger('discount_value'); // 20 (%) or 50000 (VND)
            $table->unsignedInteger('min_order_value')->default(0);
            $table->unsignedInteger('max_discount_amount')->nullable();
            $table->unsignedInteger('max_uses')->default(100);
            $table->unsignedInteger('used_count')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('applicable_to')->default('Tất cả sự kiện');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
