<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetTransactions extends Command
{
    protected $signature = 'app:reset-transactions';
    protected $description = 'مسح كافة حركات المبيعات والمشتريات والمخازن وتصفير الأرصدة';

    public function handle()
    {
        if (!$this->confirm('سيتم مسح كافة الحركات المالية والمخزنية تماماً، هل أنت متأكد؟')) {
            return;
        }

        $this->info('جاري بدء عملية تنظيف البيانات...');

        try {
            // 1. تعطيل قيود المفاتيح الأجنبية
            Schema::disableForeignKeyConstraints();

            // --- أ. مسح الجداول (Truncate يعيد الـ ID إلى 1) ---
            $tables = [
                'item_movements',
                'shortages',
                'sales_details',
                'sales_headers',
                'purchases_details',
                'purchases_headers',
                'inventory_details',
                'inventory_headers',
                'treasury_transactions',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $this->comment("جاري تنظيف جدول: {$table}");
                    DB::table($table)->truncate();
                }
            }

            // --- ب. تصفير الأرصدة الحالية ---
            $this->comment('جاري تصفير أرصدة المخازن والشركاء...');

            DB::table('warehouse_items')->update(['current_qty' => 0]);
            DB::table('partners')->update(['current_balance' => 0]);

            // 2. إعادة تفعيل القيود
            Schema::enableForeignKeyConstraints();

            $this->info('تم تنظيف كافة حركات النظام وتصفير الأرصدة بنجاح!');

        } catch (\Exception $e) {
            Schema::enableForeignKeyConstraints();
            $this->error('حدث خطأ أثناء التنظيف: ' . $e->getMessage());
        }
    }
}
