<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'name'           => fake()->words(2, true),
            'barcode'        => (string) fake()->unique()->ean13(),
            'category'       => fake()->randomElement(['food', 'drink', 'misc']),
            'image_url'      => null,
            'quantity_alert' => 5,
            'min_order'      => 1,
            'stock_quantity' => 100,
        ];
    }
}
