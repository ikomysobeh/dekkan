<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentReceipt>
 */
class PaymentReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date'    => now(),
            'type'    => fake()->randomElement(['payment', 'receipt']),
            'amount'  => 100.00,
            'notes'   => null,
        ];
    }
}
