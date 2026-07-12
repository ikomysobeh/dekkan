<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_store_creates_purchase_and_increments_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        $this->postJson('/api/purchases', [
            'products' => [[
                'product_id'     => $product->id,
                'quantity'       => 10,
                'purchase_price' => 2.00,
                'selling_price'  => 3.50,
            ]],
        ])->assertStatus(201)->assertJson(['success' => true]);

        $this->assertEquals(110, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('purchases', ['product_id' => $product->id, 'quantity' => 10]);
    }

    /** B4: deleting a purchase removes the stock it added. */
    public function test_destroy_decrements_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $purchase = Purchase::factory()->create(['product_id' => $product->id, 'quantity' => 10]);

        $this->deleteJson("/api/purchases/{$purchase->id}")->assertOk();

        $this->assertEquals(90, $product->fresh()->stock_quantity);
        $this->assertDatabaseMissing('purchases', ['id' => $purchase->id]);
    }
}
