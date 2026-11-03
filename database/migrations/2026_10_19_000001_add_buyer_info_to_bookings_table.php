<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Thông tin người nhận vé khách nhập ở giỏ vé (có thể khác hồ sơ tài khoản).
            $table->string('buyer_name', 100)->nullable()->after('payment_method');
            $table->string('buyer_phone', 20)->nullable()->after('buyer_name');
            $table->string('buyer_email')->nullable()->after('buyer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['buyer_name', 'buyer_phone', 'buyer_email']);
        });
    }
};
