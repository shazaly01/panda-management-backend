<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_items', function (Blueprint $table) {
            // المفاتيح الخارجية
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();

            // البيانات المخزنية
            // 12,4 كما اتفقنا للكميات لدعم الكسور الدقيقة
            $table->decimal('current_qty', 12, 4)->default(0);

            // حد الطلب الخاص بهذا المخزن (قد يختلف عن مخزن آخر)
            $table->decimal('alert_qty', 12, 4)->default(0);

            // مكان الرف (A-12, B-05...)
            $table->string('shelf_location')->nullable();

            // منع تكرار نفس الصنف في نفس المخزن مرتين
            $table->unique(['warehouse_id', 'item_id']);

            // Index للسرعة عند البحث عن كمية صنف معين
            $table->index(['warehouse_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_items');
    }
};
