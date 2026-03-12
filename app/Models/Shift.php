<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_open' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',

        // المبالغ المالية
        'start_cash' => 'decimal:4',
        'end_cash_system' => 'decimal:4',
        'end_cash_actual' => 'decimal:4',
        'variance' => 'decimal:4',
    ];

    // --- العلاقات ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function treasury(): BelongsTo
    {
        return $this->belongsTo(Treasury::class);
    }

    // الفواتير التي تمت خلال هذه الوردية (اختياري، للتقارير)
    public function sales(): HasMany
    {
        return $this->hasMany(SalesHeader::class);
    }
}
