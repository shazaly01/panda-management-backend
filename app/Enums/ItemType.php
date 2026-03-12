<?php

namespace App\Enums;

enum ItemType: int // أو string حسب ما اخترته في الميجريشن (غالباً int: tinyInteger)
{
    case STORE = 1; // مخزني
    case SERVICE = 2; // خدمي
    case EXPIRE = 3; // بصلاحية

    // دالة مساعدة
    public function label(): string
    {
        return match($this) {
            self::STORE => 'Store Item',
            self::SERVICE => 'Service',
            self::EXPIRE => 'Expiry Item',
        };
    }
}
