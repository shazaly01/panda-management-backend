<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();

            // تم استخدام DECIMAL(18, 0) للرقم المرجعي للحركة بدلاً من string أو integer
            $table->decimal('transaction_no', 18, 0)->nullable()->comment('رقم الحركة');

            $table->string('trx_type')->comment('نوع الحركة مثل: sales, purchases, transfer');

            // علاقة متعددة الأشكال (Polymorphic) لربط الحركة بالفاتورة أو التسوية
            // ستنشئ حقلين: reference_type و reference_id
            $table->nullableMorphs('reference');

            // الأرصدة والكميات بدقة 4 أرقام عشرية
            $table->decimal('previous_qty', 15, 4)->comment('الرصيد قبل الحركة');
            $table->decimal('trx_qty', 15, 4)->comment('كمية الحركة (موجب للوارد، سالب للمنصرف)');
            $table->decimal('current_qty', 15, 4)->comment('الرصيد النهائي بعد الحركة');

            $table->text('notes')->nullable()->comment('ملاحظات أو بيان الحركة');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_movements');
    }
};
