<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_treasuries', function (Blueprint $table) {
            $table->id();

            // الربط بين المستخدم والخزينة
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('treasury_id')->constrained('treasuries')->cascadeOnDelete();

            // هل هذه هي الخزينة الافتراضية للقبض؟
            $table->boolean('is_default')->default(false);

            // هل يسمح للمستخدم برؤية كم يوجد في الخزينة؟ (هام للكاشير)
            $table->boolean('can_view_balance')->default(false);

            // منع التكرار
            $table->unique(['user_id', 'treasury_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_treasuries');
    }
};
