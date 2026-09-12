<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'reference' => $this->faker->unique()->numerify('######'),
            'payer_phone' => '0414' . $this->faker->numerify('#######'),
            'payer_bank_code' => '0102',
            'amount' => $this->faker->randomFloat(2, 5, 200),
            'currency' => 'VES',
            'status' => Transaction::STATUS_PENDING,
        ];
    }
}
