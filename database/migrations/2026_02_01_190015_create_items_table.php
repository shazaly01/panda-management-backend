<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('items', function (Blueprint $table) {
        $table->id();

        // التصنيف
        $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();

        // البيانات الأساسية
        // تذكر قاعدتك: الأكواد دائماً DECIMAL(18,0)
        $table->decimal('code', 18, 0)->unique();
        $table->string('name');
        $table->string('barcode')->nullable()->unique();
        $table->tinyInteger('type'); // سيتم التعامل معه كـ Enum

        // الوحدة الأساسية (إجبارية)
        $table->foreignId('unit1_id')->constrained('units');
        $table->decimal('price1', 12, 4);

        // الوحدة الثانية (اختيارية - Nullable)
        // لاحظ: سطر واحد فقط للتعريف والربط
        $table->foreignId('unit2_id')->nullable()->constrained('units');
        $table->decimal('factor2', 12, 4)->nullable();
        $table->decimal('price2', 12, 4)->nullable();

        // الوحدة الثالثة (اختيارية - Nullable)
        $table->foreignId('unit3_id')->nullable()->constrained('units');
        $table->decimal('factor3', 12, 4)->nullable();
        $table->decimal('price3', 12, 4)->nullable();

        // التكاليف والحالة
        $table->decimal('base_cost', 12, 4)->default(0);
        $table->boolean('has_expiry')->default(false);
        $table->boolean('is_active')->default(true);

        $table->timestamps();
        $table->softDeletes();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
