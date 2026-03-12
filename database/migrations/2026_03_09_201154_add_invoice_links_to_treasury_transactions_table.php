<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            // إضافة معرفات الفواتير (Nullable لأن السند قد يكون مستقلاً وليس له فاتورة)
            $table->foreignId('sales_header_id')->nullable()->constrained('sales_headers')->nullOnDelete();
            $table->foreignId('purchases_header_id')->nullable()->constrained('purchases_headers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->dropForeign(['sales_header_id']);
            $table->dropForeign(['purchases_header_id']);
            $table->dropColumn(['sales_header_id', 'purchases_header_id']);
        });
    }
};
