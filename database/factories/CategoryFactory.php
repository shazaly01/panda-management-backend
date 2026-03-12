<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(), // تعديل هنا
            'code' => fake()->unique()->bothify('CAT-####'),
            'parent_id' => null,
            'is_active' => true,
        ];
    }
}
