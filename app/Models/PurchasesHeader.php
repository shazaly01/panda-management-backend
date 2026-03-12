<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchasesHeader extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'trx_no' => 'string',
        'supplier_invoice_no' => 'string', // نصي
        'invoice_date' => 'date',
        'status' => TransactionStatus::class, // ربط الـ Enum

        // المبالغ
        'total_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'remaining_amount' => 'decimal:4',
    ];

    // --- العلاقات ---

    public function details(): HasMany
    {
        return $this->hasMany(PurchasesDetail::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class); // المورد
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class); // المخزن المستلم
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class); // خزينة الدفع
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
