<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortages', function (Blueprint $table) {
            $table->id();

            // نربطه بفاتورة المبيعات المسببة للعجز
            $table->foreignId('sales_header_id')->constrained('sales_headers')->cascadeOnDelete();

            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('warehouse_id')->constrained('warehouses'); // أي مخزن حدث فيه العجز

            // الكمية التي "بعناها وهي غير موجودة"
            $table->decimal('shortage_qty', 12, 4);

            // الحالة: pending (معلق), resolved (تمت التسوية)
            $table->string('status')->default('pending')->index();

            // متى تمت تسويته؟ (عند شراء بضاعة جديدة)
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortages');
    }
};
