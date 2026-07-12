<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'product_id'     => Product::factory(),
            'date'           => now(),
            'quantity'       => 10,
            'purchase_price' => 2.00,
            'selling_price'  => 3.50,
        ];
    }
}
