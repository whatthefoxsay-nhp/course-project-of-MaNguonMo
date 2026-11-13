<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'discount_id')) {
                $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete()->after('total_price');
            }
            if (! Schema::hasColumn('bookings', 'discount_amount')) {
                $table->unsignedInteger('discount_amount')->default(0)->after('discount_id');
            }
            if (! Schema::hasColumn('bookings', 'buyer_name')) {
                $table->string('buyer_name')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('bookings', 'buyer_phone')) {
                $table->string('buyer_phone', 20)->nullable()->after('buyer_name');
            }
            if (! Schema::hasColumn('bookings', 'buyer_email')) {
                $table->string('buyer_email')->nullable()->after('buyer_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'discount_id')) {
                $table->dropForeign(['discount_id']);
                $table->dropColumn('discount_id');
            }
            if (Schema::hasColumn('bookings', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('bookings', 'buyer_name')) {
                $table->dropColumn('buyer_name');
            }
            if (Schema::hasColumn('bookings', 'buyer_phone')) {
                $table->dropColumn('buyer_phone');
            }
            if (Schema::hasColumn('bookings', 'buyer_email')) {
                $table->dropColumn('buyer_email');
            }
        });
    }
};

