<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'has_expiry' => 'boolean',
        'code' => 'string', // للحفاظ على دقة الـ Decimal
        'barcode' => 'string',
        'type' => ItemType::class, // ربط الـ Enum تلقائياً

        // الأرقام المالية نحولها لـ float أو نتركها string للعمليات الحسابية الدقيقة
        'price1' => 'decimal:4',
        'price2' => 'decimal:4',
        'price3' => 'decimal:4',
        'base_cost' => 'decimal:4',
    ];

    // --- العلاقات الأساسية ---

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // علاقات الوحدات (نربطها بنفس الموديل Unit)
    public function unit1(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit1_id');
    }

    public function unit2(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit2_id');
    }

    public function unit3(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit3_id');
    }

    // --- علاقة الأرصدة (الأهم) ---
    // هذه العلاقة تجلب لك المخازن التي يتوفر بها هذا الصنف مع الكمية
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_items')
                    ->withPivot(['current_qty', 'alert_qty', 'shelf_location']);
    }
}
