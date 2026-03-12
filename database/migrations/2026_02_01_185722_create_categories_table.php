<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            // المعرف الداخلي للنظام (للسرعة والعلاقات)
            $table->id();

            // كود التصنيف (المعرف التجاري - DECIMAL 18,0 كما اتفقنا)
            $table->decimal('code', 18, 0)->unique()->index();

            // الاسم
            $table->string('name');

            // الهيكلية الشجرية (Self-Referencing) لعمل تصنيفات فرعية
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categories')
                  ->nullOnDelete();

            // لون لتمييز القسم في شاشة الكاشير (اختياري)
            $table->string('color')->nullable();

            // الحالة (مفعل/غير مفعل)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
