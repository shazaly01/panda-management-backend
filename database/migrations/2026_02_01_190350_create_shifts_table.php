<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            // الموظف والخزينة التي يعمل عليها
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('treasury_id')->constrained('treasuries');

            // التوقيتات
            $table->timestamp('started_at'); // وقت الفتح
            $table->timestamp('ended_at')->nullable(); // وقت الإغلاق

            // الماليات (DECIMAL 20,4 للمبالغ المالية)
            $table->decimal('start_cash', 20, 4)->default(0); // عهدة البداية (الفكة)

            // هذه الحقول تعبأ عند الإغلاق
            $table->decimal('end_cash_system', 20, 4)->nullable(); // ما يقوله النظام
            $table->decimal('end_cash_actual', 20, 4)->nullable(); // ما عده الكاشير بيده
            $table->decimal('variance', 20, 4)->default(0); // الفرق (عجز او زيادة)

            // الحالة: 1=مفتوحة، 0=مغلقة
            $table->boolean('is_open')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
