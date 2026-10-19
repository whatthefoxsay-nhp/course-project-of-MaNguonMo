<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Mã giảm giá áp dụng lúc thanh toán; total_price là số tiền SAU giảm.
            $table->foreignId('discount_id')->nullable()->after('total_price')->constrained('discounts')->nullOnDelete();
            $table->unsignedInteger('discount_amount')->default(0)->after('discount_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_id');
            $table->dropColumn('discount_amount');
        });
    }
};
