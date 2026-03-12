<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_details', function (Blueprint $table) {
            $table->id();

            // الربط مع رأس الفاتورة (حذف الرأس يحذف التفاصيل)
            $table->foreignId('sales_header_id')->constrained('sales_headers')->cascadeOnDelete();

            // الصنف
            $table->foreignId('item_id')->constrained('items')->onDelete('restrict');

            // الوحدة التي تم البيع بها (كرتونة، حبة..)
            $table->foreignId('unit_id')->constrained('units');

            // معامل التحويل المستخدم (لحفظ تاريخ التحويل لو تغير لاحقاً)
            $table->decimal('unit_factor', 12, 4)->default(1);

            // الكمية (12,4)
            $table->decimal('qty', 12, 4);

            // الأسعار (20,4)
            $table->decimal('price', 20, 4); // سعر البيع للوحدة

            // *** الحقل الأهم للأرباح ***
            // يتم تخزين تكلفة الوحدة "لحظة البيع" هنا
            $table->decimal('cost', 20, 4);

            $table->decimal('total_row', 20, 4); // (qty * price)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_details');
    }
};
