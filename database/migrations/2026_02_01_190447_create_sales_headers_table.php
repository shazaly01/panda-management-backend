<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_headers', function (Blueprint $table) {
            $table->id();

            // رقم الفاتورة (DECIMAL 18,0) - فريد
            $table->decimal('trx_no', 18, 0)->unique()->index();

            // التوقيت
            $table->date('invoice_date')->index();

            // الأطراف
            $table->foreignId('partner_id')->constrained('partners')->onDelete('restrict'); // العميل
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict'); // المخزن

            // الخزينة (قد تكون null في الفواتير الآجلة بالكامل، أو نضع خزينة افتراضية)
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries');

            // الوردية (اختياري)
            $table->foreignId('shift_id')->nullable()->constrained('shifts');

            // الماليات (DECIMAL 20,4)
            $table->decimal('total_amount', 20, 4)->default(0); // قبل الخصم والضريبة
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('net_amount', 20, 4)->default(0); // النهائي المطلوب

            $table->decimal('paid_amount', 20, 4)->default(0); // المدفوع
            $table->decimal('remaining_amount', 20, 4)->default(0); // المتبقي (آجل)

            // الحالة (draft, confirmed, returned)
            $table->string('status')->default('confirmed')->index();

            // الموظف الذي أنشأ الفاتورة
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_headers');
    }
};
