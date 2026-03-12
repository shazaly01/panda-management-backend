<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Category;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true), // تعديل هنا (بدل productName)
            'code' => fake()->unique()->ean8(),
            'barcode' => fake()->ean13(),
            'type' => fake()->randomElement(ItemType::cases()),

            'unit1_id' => Unit::factory(),
            'price1' => fake()->randomFloat(2, 10, 500),

            'unit2_id' => null,
            'factor2' => null,
            'price2' => null,

            'base_cost' => fake()->randomFloat(2, 5, 300),
            'has_expiry' => fake()->boolean(20),
            'is_active' => true,
        ];
    }
}
