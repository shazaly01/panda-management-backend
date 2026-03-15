<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_headers', function (Blueprint $table) {
            // إضافة حقل إجمالي الأمتار
            $table->decimal('total_area', 18, 4)->nullable()->after('net_amount')
                  ->comment('إجمالي عدد الأمتار لجميع أصناف الفاتورة');
        });
    }

    public function down(): void
    {
        Schema::table('sales_headers', function (Blueprint $table) {
            $table->dropColumn('total_area');
        });
    }
};
