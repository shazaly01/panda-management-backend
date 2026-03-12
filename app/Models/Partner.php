<?php

namespace App\Models;

use App\Enums\PartnerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'code' => 'string', // للحفاظ على دقة المعرف
        'type' => PartnerType::class, // تحويل تلقائي للـ Enum
        'current_balance' => 'decimal:4', // التعامل معه كرقم عشري دقيق
    ];
}
