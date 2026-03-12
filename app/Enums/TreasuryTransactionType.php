<?php

namespace App\Enums;

enum TreasuryTransactionType: string
{
    case RECEIPT = 'receipt'; // سند قبض (استلام نقدية)
    case PAYMENT = 'payment'; // سند صرف (دفع نقدية)

    public function label(): string
    {
        return match($this) {
            self::RECEIPT => 'سند قبض',
            self::PAYMENT => 'سند صرف',
        };
    }
}
