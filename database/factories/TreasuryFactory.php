<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TreasuryFactory extends Factory
{
    public function definition(): array
    {
        $isBank = fake()->boolean();
        return [
            'name' => $isBank ? fake()->creditCardType() . ' Bank' : 'Main Cashier',
            'code' => fake()->unique()->bothify('TRS-##'),
            'is_bank' => $isBank,
            'bank_account_no' => $isBank ? fake()->bankAccountNumber() : null,
            'current_balance' => 0, // نبدأ دائماً بصفر
            'is_active' => true,
        ];
    }
}
