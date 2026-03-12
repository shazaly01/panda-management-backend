<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Partner;
use App\Models\Treasury;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesHeaderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trx_no' => fake()->unique()->bothify('INV-S-#####'),
            'invoice_date' => fake()->date(),
            'status' => TransactionStatus::CONFIRMED,

            'partner_id' => Partner::factory(),
            'warehouse_id' => Warehouse::factory(),
            'treasury_id' => Treasury::factory(),
            'created_by' => User::factory(), // يفترض وجود Factory لليوزر من لارافيل

            'total_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'net_amount' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 0,
        ];
    }
}
