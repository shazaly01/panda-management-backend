<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();

            // رقم السند - باستخدام Decimal(18,0) لضمان دقة المعرفات الرقمية الطويلة
            $table->decimal('trx_no', 18, 0)->unique();
            $table->date('trx_date');

            // نوع السند (receipt أو payment)
            $table->string('type');

            // العلاقات
            $table->foreignId('treasury_id')->constrained('treasuries')->restrictOnDelete();
            // الـ partner قد يكون nullable في حال كان السند لمصروفات عامة وليس لعميل/مورد
            $table->foreignId('partner_id')->nullable()->constrained('partners')->restrictOnDelete();

            // المبلغ المالي بدقة 4 أرقام عشرية
            $table->decimal('amount', 15, 4);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};
