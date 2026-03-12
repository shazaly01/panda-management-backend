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
        Schema::table('users', function (Blueprint $table) {
            // إضافة الحقول كروابط اختيارية (Nullable)
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            // الخزينة تشير لجدول treasuries
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->nullOnDelete();

            // البنك يشير أيضاً لجدول treasuries (لأن is_bank موجود بداخله)
            $table->foreignId('bank_id')->nullable()->constrained('treasuries')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // إزالة العلاقات ثم الحقول في حالة التراجع
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['treasury_id']);
            $table->dropForeign(['bank_id']);

            $table->dropColumn(['warehouse_id', 'treasury_id', 'bank_id']);
        });
    }
};
