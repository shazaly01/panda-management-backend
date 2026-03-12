<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Treasury extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_bank' => 'boolean',
        'code' => 'string',
        'current_balance' => 'decimal:4',
    ];

    // المستخدمين المسموح لهم التعامل مع هذه الخزينة
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_treasuries')
                    ->withPivot(['is_default', 'can_view_balance'])
                    ->withTimestamps();
    }
}
