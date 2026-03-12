<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case TRANSFER = 'transfer';             // تحويل بين مخزنين
    case ADJUSTMENT_IN = 'adjustment_in';   // تسوية بالزيادة (جرد)
    case ADJUSTMENT_OUT = 'adjustment_out'; // تسوية بالنقص (جرد)
    case DAMAGE = 'damage';
    case SALES = 'sales';
    case PURCHASES = 'purchases';

    public function label(): string
    {
        return match($this) {
            self::TRANSFER => 'تحويل مخزني',
            self::ADJUSTMENT_IN => 'تسوية زيادة',
            self::ADJUSTMENT_OUT => 'تسوية عجز',
            self::DAMAGE => 'إتلاف',
            self::SALES => 'مبيعات',
            self::PURCHASES => 'مشتريات',
        };
    }
}
