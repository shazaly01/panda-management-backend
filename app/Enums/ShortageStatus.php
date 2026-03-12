<?php

namespace App\Enums;

enum ShortageStatus: string
{
    case PENDING = 'pending';   // معلق (لم يتم شراء بضاعة لتغطيته بعد)
    case RESOLVED = 'resolved'; // تمت التسوية (تم شراء البضاعة وتغطية العجز)

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'معلق',
            self::RESOLVED => 'تمت التسوية',
        };
    }
}
