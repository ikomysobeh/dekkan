<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Sale;
use App\Models\Product;
use App\Models\PaymentReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_headline_numbers(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $product = Product::factory()->create(['stock_quantity' => 1, 'quantity_alert' => 5]);
        Sale::factory()->create([
            'product_id' => $product->id,
            'date_time'  => now(),
            'total_price' => 30.00,
        ]);
        PaymentReceipt::factory()->create(['type' => 'receipt', 'amount' => 100]);
        PaymentReceipt::factory()->create(['type' => 'payment', 'amount' => 40]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'today_sales'     => 30.0,
                    'low_stock_count' => 1,
                    'cash_balance'    => 60.0, // 100 - 40
                ],
            ]);
    }
}
