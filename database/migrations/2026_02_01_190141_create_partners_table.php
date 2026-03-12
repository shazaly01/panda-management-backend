<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();

            // كود العميل/المورد (DECIMAL 18,0)
            $table->decimal('code', 18, 0)->unique()->index();

            $table->string('name');

            // 1: Customer, 2: Supplier, 3: Both
            $table->tinyInteger('type')->index();

            // بيانات الاتصال
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_number')->nullable(); // الرقم الضريبي
            $table->string('address')->nullable();

            // الرصيد الحالي (Cache Column)
            // يُحدث تلقائياً مع الفواتير والسندات لسرعة استخراج كشف الحساب
            $table->decimal('current_balance', 20, 4)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
