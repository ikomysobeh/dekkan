<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_index_lists_products_paginated(): void
    {
        Product::factory()->count(3)->create();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_by_barcode_returns_product_with_price(): void
    {
        $product = Product::factory()->create(['barcode' => '11122233344']);
        Purchase::factory()->create(['product_id' => $product->id, 'selling_price' => 4.25]);

        $this->getJson('/api/products/by-barcode/11122233344')
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['barcode' => '11122233344', 'selling_price' => 4.25]]);
    }

    public function test_by_barcode_returns_404_when_missing(): void
    {
        $this->getJson('/api/products/by-barcode/does-not-exist')
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_search_finds_by_name(): void
    {
        Product::factory()->create(['name' => 'Special Coffee']);

        $this->getJson('/api/products/search?query=Coffee')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Special Coffee']);
    }

    public function test_alerts_lists_low_stock_products(): void
    {
        Product::factory()->create(['name' => 'Low', 'stock_quantity' => 2, 'quantity_alert' => 5]);
        Product::factory()->create(['name' => 'Fine', 'stock_quantity' => 50, 'quantity_alert' => 5]);

        $this->getJson('/api/products/alerts')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Low'])
            ->assertJsonMissing(['name' => 'Fine']);
    }

    public function test_store_creates_a_product(): void
    {
        $this->postJson('/api/products', [
            'name'           => 'New Item',
            'barcode'        => '55566677788',
            'category'       => 'drink',
            'quantity_alert' => 5,
            'min_order'      => 1,
            'stock_quantity' => 20,
        ])->assertStatus(201)
          ->assertJson(['success' => true, 'data' => ['name' => 'New Item', 'barcode' => '55566677788']]);

        $this->assertDatabaseHas('products', ['name' => 'New Item', 'barcode' => '55566677788']);
    }

    /** B3: the API can edit barcode + category (the web version cannot). */
    public function test_update_can_change_barcode_and_category(): void
    {
        $product = Product::factory()->create(['barcode' => 'OLD', 'category' => 'food']);

        $this->postJson("/api/products/{$product->id}", [
            'barcode'  => 'NEW',
            'category' => 'drink',
        ])->assertOk();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'barcode' => 'NEW', 'category' => 'drink']);
    }

    public function test_destroy_deletes_a_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
