<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_headers', function (Blueprint $table) {
            $table->id();

            // رقم الحركة (DECIMAL 18,0)
            $table->decimal('trx_no', 18, 0)->unique()->index();

            $table->date('trx_date')->index();

            // نوع الحركة: (transfer, adjustment_in, adjustment_out, damage)
            $table->string('trx_type')->index();

            // المخازن (Null حسب نوع الحركة)
            // في التحويل نحتاج الاثنين، في الجرد نحتاج واحداً فقط
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses');

            // ملاحظات
            $table->text('notes')->nullable();

            // الحالة (draft, confirmed)
            $table->string('status')->default('confirmed');

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_headers');
    }
};
