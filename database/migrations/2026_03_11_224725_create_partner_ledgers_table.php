<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();

            // رقم الحركة، استخدمنا DECIMAL(18, 0) كما هو متبع لديك للأرقام التسلسلية الطويلة
            $table->decimal('transaction_no', 18, 0)->nullable();

            $table->tinyInteger('transaction_type'); // سيأخذ القيمة من PartnerTransactionType Enum
            $table->unsignedBigInteger('reference_id')->nullable(); // للربط بـ id الفاتورة أو السند

            // المبالغ المالية (مدين ودائن)
            $table->decimal('debit', 15, 4)->default(0);  // مدين (عليه)
            $table->decimal('credit', 15, 4)->default(0); // دائن (له)

            $table->string('notes')->nullable(); // ملاحظات الحركة
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_ledgers');
    }
};
