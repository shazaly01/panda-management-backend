<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'code' => 'string',
    ];

    // الأصناف الموجودة داخل هذا المخزن
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'warehouse_items')
                    ->withPivot(['current_qty', 'alert_qty', 'shelf_location']);
    }

    // المستخدمين المسموح لهم الوصول لهذا المخزن
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_warehouses')
                    ->withPivot(['is_default'])
                    ->withTimestamps();
    }
}
