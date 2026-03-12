<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_items', function (Blueprint $table) {
            // إضافة عمود الـ id في أول الجدول وجعله Primary Key تلقائياً
            // سيقوم MySQL تلقائياً بتوليد أرقام تسلسلية للبيانات الحالية
            $table->id()->first();

            // إضافة التوقيتات (اختياري لكن مفيد جداً)
            if (!Schema::hasColumn('warehouse_items', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_items', function (Blueprint $table) {
            $table->dropColumn(['id', 'created_at', 'updated_at']);
        });
    }
};
