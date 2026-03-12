<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryHeader extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'trx_no' => 'string',
        'trx_date' => 'date',
        'trx_type' => InventoryTransactionType::class, // Enum: Transfer, Adjustment...
        'status' => TransactionStatus::class,
    ];

    // --- العلاقات ---

    public function details(): HasMany
    {
        return $this->hasMany(InventoryDetail::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
