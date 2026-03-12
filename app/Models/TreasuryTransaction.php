<?php

namespace App\Models;

use App\Enums\TreasuryTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreasuryTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'trx_no' => 'decimal:0', // تحويل مباشر للحفاظ على رقم الحركة بدون كسور
        'trx_date' => 'date',
        'type' => TreasuryTransactionType::class, // ربط الـ Enum التلقائي
        'amount' => 'decimal:4',
    ];

    // --- العلاقات ---

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function salesHeader(): BelongsTo
    {
        return $this->belongsTo(SalesHeader::class, 'sales_header_id');
    }

    public function purchasesHeader(): BelongsTo
    {
        return $this->belongsTo(PurchasesHeader::class, 'purchases_header_id');
    }
}
