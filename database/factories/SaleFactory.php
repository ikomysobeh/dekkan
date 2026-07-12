<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'product_id'  => Product::factory(),
            'date_time'   => now(),
            'quantity'    => 1,
            'total_price' => 3.50,
        ];
    }
}
