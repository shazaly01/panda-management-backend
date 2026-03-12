<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'decimal:4',
        'price' => 'decimal:4',
        'cost' => 'decimal:4', // التكلفة (هام جداً)
        'total_row' => 'decimal:4',
        'unit_factor' => 'decimal:4',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'area' => 'decimal:4',
    ];

    // العودة لرأس الفاتورة
    public function header(): BelongsTo
    {
        return $this->belongsTo(SalesHeader::class, 'sales_header_id');
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
