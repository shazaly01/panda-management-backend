<?php

namespace Database\Factories;

use App\Enums\PartnerType;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => fake()->unique()->bothify('PRT-####'),
            'type' => fake()->randomElement(PartnerType::cases()),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'tax_number' => fake()->numerify('#########'),
            'current_balance' => 0,
            'is_active' => true,
        ];
    }
}
