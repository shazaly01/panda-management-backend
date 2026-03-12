<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_header_id')->constrained('inventory_headers')->cascadeOnDelete();

            $table->foreignId('item_id')->constrained('items')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('units');

            // الكمية (12,4)
            $table->decimal('qty', 12, 4);

            // معامل التحويل (لأننا نخزن دائماً بالوحدة الصغرى داخلياً)
            $table->decimal('unit_factor', 12, 4)->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_details');
    }
};
