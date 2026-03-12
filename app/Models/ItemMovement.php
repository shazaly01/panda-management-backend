<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemMovement extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_no' => 'decimal:0', // تعيين الـ Cast ليتوافق مع DECIMAL(18, 0)
        'trx_type' => InventoryTransactionType::class,
        'previous_qty' => 'decimal:4',
        'trx_qty' => 'decimal:4',
        'current_qty' => 'decimal:4',
    ];

    /**
     * المخزن الذي تمت عليه الحركة
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * الصنف المرتبط بالحركة
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * المستند المرجعي للحركة (فاتورة مبيعات، تسوية، تحويل، إلخ)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
