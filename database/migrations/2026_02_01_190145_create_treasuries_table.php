<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasuries', function (Blueprint $table) {
            $table->id();

            // كود الخزينة (DECIMAL 18,0)
            $table->decimal('code', 18, 0)->unique()->index();

            $table->string('name');

            // هل هي بنك؟ (true = بنك، false = خزينة نقدية)
            $table->boolean('is_bank')->default(false);

            // رقم الحساب البنكي (يظهر فقط للبنوك)
            $table->string('bank_account_no')->nullable();

            // الرصيد الحالي
            $table->decimal('current_balance', 20, 4)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasuries');
    }
};
