<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_store_creates_sale_and_decrements_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        Purchase::factory()->create(['product_id' => $product->id, 'selling_price' => 3.50]);

        $this->postJson('/api/sales', [
            'products' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertStatus(201)
          ->assertJson(['success' => true, 'grand_total' => 10.5]);

        $this->assertEquals(97, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('sales', ['product_id' => $product->id, 'quantity' => 3]);
    }

    public function test_store_rejects_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 2]);

        $this->postJson('/api/sales', [
            'products' => [['product_id' => $product->id, 'quantity' => 5]],
        ])->assertStatus(422)->assertJson(['success' => false]);

        // Stock unchanged (transaction rolled back)
        $this->assertEquals(2, $product->fresh()->stock_quantity);
    }

    /** B4: deleting a sale restores stock. */
    public function test_destroy_restores_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $sale = Sale::factory()->create(['product_id' => $product->id, 'quantity' => 4]);

        $this->deleteJson("/api/sales/{$sale->id}")->assertOk();

        $this->assertEquals(104, $product->fresh()->stock_quantity);
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_update_adjusts_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        Purchase::factory()->create(['product_id' => $product->id, 'selling_price' => 2.00]);
        $sale = Sale::factory()->create(['product_id' => $product->id, 'quantity' => 5]);
        // Simulate the stock already reduced by that sale
        $product->update(['stock_quantity' => 95]);

        $this->putJson("/api/sales/{$sale->id}", [
            'date_time'  => now()->toDateTimeString(),
            'product_id' => $product->id,
            'quantity'   => 8,
        ])->assertOk();

        // revert +5 (=100) then apply -8 (=92)
        $this->assertEquals(92, $product->fresh()->stock_quantity);
    }
}
