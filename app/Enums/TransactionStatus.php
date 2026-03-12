<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case DRAFT = 'draft';         // مسودة (لم تؤثر على المخزن بعد)
    case CONFIRMED = 'confirmed'; // معتمد (تم الخصم/الإضافة)
    case CANCELLED = 'cancelled'; // ملغي

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::CONFIRMED => 'معتمد',
            self::CANCELLED => 'ملغي',
        };
    }
}
