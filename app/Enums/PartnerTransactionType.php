<?php

namespace App\Enums;

enum PartnerTransactionType: int
{
    case SALES_INVOICE = 1;      // فاتورة مبيعات (تزيد مديونية العميل)
    case SALES_RETURN = 2;       // مرتجع مبيعات (تنقص مديونية العميل)
    case RECEIPT = 3;            // سند قبض / استلام نقدية (ينقص مديونية العميل)
    case PAYMENT = 4;            // سند صرف / دفع نقدية (يزيد مديونية العميل / ينقص مديونية المورد)
    case OPENING_BALANCE = 5;    // رصيد افتتاحي
    case PURCHASE_INVOICE = 6;   // فاتورة مشتريات (تزيد الدائنية) <-- أضفنا هذه
    case PURCHASE_RETURN = 7;    // مرتجع مشتريات

    public function label(): string
    {
        return match($this) {
            self::SALES_INVOICE => 'فاتورة مبيعات',
            self::SALES_RETURN => 'مرتجع مبيعات',
            self::RECEIPT => 'سند قبض',
            self::PAYMENT => 'سند صرف',
            self::OPENING_BALANCE => 'رصيد افتتاحي',
            self::PURCHASE_INVOICE => 'فاتورة مشتريات',
            self::PURCHASE_RETURN => 'مرتجع مشتريات',
        };
    }
}
