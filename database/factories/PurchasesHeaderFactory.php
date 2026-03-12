<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Partner;
use App\Models\Treasury;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchasesHeaderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'trx_no' => fake()->unique()->bothify('INV-P-#####'),
            'supplier_invoice_no' => fake()->bothify('SUP-###'),
            'invoice_date' => fake()->date(),
            'status' => TransactionStatus::CONFIRMED,

            'partner_id' => Partner::factory(),
            'warehouse_id' => Warehouse::factory(),
            'treasury_id' => Treasury::factory(),
            'created_by' => User::factory(),

            'total_amount' => 0,
            'net_amount' => 0,
        ];
    }
}
