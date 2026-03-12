<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();

            // كود الوحدة (DECIMAL 18,0)
            $table->decimal('code', 18, 0)->unique()->index();

            // اسم الوحدة (قطعة، علبة، كرتونة)
            $table->string('name');

            // حالة التفعيل
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
