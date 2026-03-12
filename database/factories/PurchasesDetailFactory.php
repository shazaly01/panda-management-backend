<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PurchasesHeader;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchasesDetailFactory extends Factory
{
    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 10, 500);
        $cost = fake()->randomFloat(2, 5, 100);

        return [
            'purchases_header_id' => PurchasesHeader::factory(),
            'item_id' => Item::factory(),
            'unit_id' => Unit::factory(),

            'qty' => $qty,
            'unit_cost' => $cost,
            'total_row' => $qty * $cost,
            'unit_factor' => 1,
            'production_date' => fake()->date(),
            'expiry_date' => fake()->dateTimeBetween('+1 year', '+2 years'),
        ];
    }
}
