<?php

namespace App\Models;

use App\Enums\PartnerTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerLedger extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_no' => 'decimal:0',
        'transaction_type' => PartnerTransactionType::class,
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
    ];

    // علاقة الحركة بالشريك (العميل أو المورد)
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
