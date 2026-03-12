<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // التغيير هنا من Pivot إلى Model

class WarehouseItem extends Model
{
    protected $table = 'warehouse_items';

    // لارافيل سيتعرف على الـ id تلقائياً الآن
    public $timestamps = true;

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'current_qty',
        'alert_qty',
        'shelf_location',
    ];
}
