<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->city() . ' Warehouse',
            'code' => fake()->unique()->bothify('WH-##'),
            'location' => fake()->address(),
            'keeper_name' => fake()->name(),
            'is_active' => true,
        ];
    }
}
