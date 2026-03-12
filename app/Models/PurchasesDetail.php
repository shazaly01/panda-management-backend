<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasesDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_cost' => 'decimal:4', // تكلفة الشراء
        'total_row' => 'decimal:4',
        'unit_factor' => 'decimal:4',

        // تواريخ الصلاحية (هام جداً)
        'production_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(PurchasesHeader::class, 'purchases_header_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
