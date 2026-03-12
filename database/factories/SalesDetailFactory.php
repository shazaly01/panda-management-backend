<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\SalesHeader;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesDetailFactory extends Factory
{
    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 100);
        $price = fake()->randomFloat(2, 10, 200);

        return [
            'sales_header_id' => SalesHeader::factory(),
            'item_id' => Item::factory(),
            'unit_id' => Unit::factory(),

            'qty' => $qty,
            'price' => $price,
            'cost' => $price * 0.8, // التكلفة افتراضياً 80% من السعر
            'total_row' => $qty * $price,
            'unit_factor' => 1,
        ];
    }
}
