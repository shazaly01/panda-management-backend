<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <-- أضف هذا السطر
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    protected string $guard_name = 'api';

    protected $fillable = [
        'full_name',
        'username',
        'email',
        'password',
        // --- الحقول الجديدة للقيم الافتراضية ---
        'warehouse_id',
        'treasury_id',
        'bank_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getNameAttribute()
    {
        return $this->full_name;
    }

    // --- العلاقات الجديدة المباشرة (القيم الافتراضية للفواتير) ---

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function defaultTreasury(): BelongsTo
    {
        // الخزينة تشير لجدول Treasuries
        return $this->belongsTo(Treasury::class, 'treasury_id');
    }

    public function defaultBank(): BelongsTo
    {
        // البنك أيضاً يشير لجدول Treasuries
        return $this->belongsTo(Treasury::class, 'bank_id');
    }

    // --------------------------------------------------------
    // (باقي الكود الخاص بك والعلاقات السابقة يبقى كما هو دون تغيير)
    // --------------------------------------------------------

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouses')
                    ->withPivot(['is_default'])
                    ->withTimestamps();
    }

    public function treasuries(): BelongsToMany
    {
        return $this->belongsToMany(Treasury::class, 'user_treasuries')
                    ->withPivot(['is_default', 'can_view_balance'])
                    ->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(SalesHeader::class, 'created_by');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PurchasesHeader::class, 'created_by');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryHeader::class, 'created_by');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }
}
