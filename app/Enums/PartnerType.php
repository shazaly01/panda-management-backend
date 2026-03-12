<?php

namespace App\Enums;

enum PartnerType: int
{
    case CUSTOMER = 1; // عميل
    case SUPPLIER = 2; // مورد
    case BOTH = 3;     // عميل ومورد معاً

    public function label(): string
    {
        return match($this) {
            self::CUSTOMER => 'عميل',
            self::SUPPLIER => 'مورد',
            self::BOTH => 'عميل ومورد',
        };
    }
}
