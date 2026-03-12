<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchases_header_id')->constrained('purchases_headers')->cascadeOnDelete();

            $table->foreignId('item_id')->constrained('items')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('units');

            // معامل التحويل المستخدم
            $table->decimal('unit_factor', 12, 4)->default(1);

            // الكمية (12,4)
            $table->decimal('qty', 12, 4);

            // سعر الشراء (التكلفة) (20,4)
            $table->decimal('unit_cost', 20, 4);
            $table->decimal('total_row', 20, 4);

            // تواريخ الإنتاج والانتهاء (للصيدليات والسوبر ماركت)
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases_details');
    }
};
