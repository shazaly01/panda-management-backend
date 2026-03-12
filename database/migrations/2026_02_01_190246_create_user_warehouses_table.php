<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_warehouses', function (Blueprint $table) {
            $table->id();

            // المستخدم (نفترض أن جدول المستخدمين هو users الافتراضي في لارافل)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // المخزن
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // هل هذا هو المخزن الافتراضي عند فتح البرنامج؟
            $table->boolean('is_default')->default(false);

            // منع التكرار
            $table->unique(['user_id', 'warehouse_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_warehouses');
    }
};
