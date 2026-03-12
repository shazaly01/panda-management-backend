<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. تحديث جدول رأس الفاتورة
        Schema::table('sales_headers', function (Blueprint $table) {
            $table->string('walk_in_customer_name')->nullable()->after('partner_id')
                  ->comment('اسم العميل المباشر في حال لم يكن مسجلا في النظام');

            $table->string('shipping_destination')->nullable()->after('walk_in_customer_name')
                  ->comment('وجهة الشحن');

            // إضافة علاقة المصمم وعمولته
            $table->foreignId('designer_id')->nullable()->constrained('users')->nullOnDelete()
                  ->after('created_by');

            $table->decimal('design_commission', 18, 4)->nullable()->after('designer_id')
                  ->comment('قيمة عمولة التصميم المستحقة لهذه الفاتورة');
        });

        // 2. تحديث جدول تفاصيل الفاتورة
        Schema::table('sales_details', function (Blueprint $table) {
            $table->string('description')->nullable()->after('item_id')
                  ->comment('بيان أو وصف للعمل مثل لوحة محل');

            $table->decimal('length', 18, 4)->nullable()->after('qty')
                  ->comment('الطول');

            $table->decimal('width', 18, 4)->nullable()->after('length')
                  ->comment('العرض');

            $table->decimal('area', 18, 4)->nullable()->after('width')
                  ->comment('المساحة الإجمالية (الطول * العرض)');
        });
    }

    public function down(): void
    {
        Schema::table('sales_headers', function (Blueprint $table) {
            $table->dropForeign(['designer_id']);
            $table->dropColumn([
                'walk_in_customer_name',
                'shipping_destination',
                'designer_id',
                'design_commission'
            ]);
        });

        Schema::table('sales_details', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'length',
                'width',
                'area'
            ]);
        });
    }
};
