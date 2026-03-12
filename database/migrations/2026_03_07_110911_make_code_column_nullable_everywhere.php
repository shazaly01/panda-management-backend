<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. جدول المستودعات
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
        });

        // 2. جدول الخزائن
        Schema::table('treasuries', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
        });

        // 3. جدول الشركاء (العملاء والموردين)
        Schema::table('partners', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
        });

        Schema::table('treasuries', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
        });
    }
};
