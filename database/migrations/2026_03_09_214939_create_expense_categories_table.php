<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();

            // كود المصروف في شجرة الحسابات (يستوعب أرقاماً طويلة جداً بدقة تامة)
            $table->decimal('expense_code', 18, 0)->unique();
            $table->string('name'); // اسم المصروف
            $table->boolean('is_active')->default(true); // حالة التفعيل

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
