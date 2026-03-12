<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases_headers', function (Blueprint $table) {
            $table->id();

            // رقم الحركة الداخلي (DECIMAL 18,0)
            $table->decimal('trx_no', 18, 0)->unique()->index();

            // رقم فاتورة المورد (للمراجعة، نصي لأنه قد يحوي حروفاً)
            $table->string('supplier_invoice_no')->nullable()->index();

            $table->date('invoice_date')->index();

            // المورد
            $table->foreignId('partner_id')->constrained('partners')->onDelete('restrict');

            // المخزن المستلم للبضاعة
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');

            // الخزينة (في حال الدفع النقدي الفوري)
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries');

            // الماليات (DECIMAL 20,4)
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('net_amount', 20, 4)->default(0);

            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->decimal('remaining_amount', 20, 4)->default(0);

            // الحالة (draft, confirmed)
            $table->string('status')->default('confirmed');

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases_headers');
    }
};
