<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesHeader extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'trx_no' => 'string', // المعرفات الكبيرة كسترينج
        'invoice_date' => 'date',
        'status' => TransactionStatus::class, // ربط الـ Enum

        // المبالغ المالية بدقة عالية
        'total_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'remaining_amount' => 'decimal:4',
        'total_area' => 'decimal:4',
        'design_commission' => 'decimal:4',
    ];

    // --- العلاقات ---

    // تفاصيل الفاتورة (الأصناف)
    public function details(): HasMany
    {
        return $this->hasMany(SalesDetail::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    // المستخدم الذي أنشأ الفاتورة
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }
}
