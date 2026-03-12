<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            // كود المخزن (DECIMAL 18,0)
            $table->decimal('code', 18, 0)->unique()->index();

            $table->string('name');
            $table->string('location')->nullable(); // العنوان
            $table->string('keeper_name')->nullable(); // اسم أمين المخزن المسؤول

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
