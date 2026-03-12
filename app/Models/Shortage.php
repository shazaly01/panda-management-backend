<?php

namespace App\Models;

use App\Enums\ShortageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shortage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'shortage_qty' => 'decimal:4',
        'status' => ShortageStatus::class, // Enum: Pending, Resolved
        'resolved_at' => 'datetime',
    ];

    // الفاتورة التي سببت العجز
    public function salesHeader(): BelongsTo
    {
        return $this->belongsTo(SalesHeader::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
