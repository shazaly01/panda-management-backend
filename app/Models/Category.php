<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        // هام: تحويل الكود لنص لمنع فقدان الدقة في الجافاسكريبت
        'code' => 'string',
        'parent_id' => 'integer',
    ];

    // العلاقة مع الأب (التصنيف الرئيسي)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // العلاقة مع الأبناء (التصنيفات الفرعية)
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // العلاقة مع الأصناف
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
