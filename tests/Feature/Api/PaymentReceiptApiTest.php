<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\PaymentReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentReceiptApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_store_creates_a_receipt(): void
    {
        $this->postJson('/api/payment-receipts', [
            'date'   => now()->toDateTimeString(),
            'type'   => 'receipt',
            'amount' => 250.00,
            'notes'  => 'initial',
        ])->assertStatus(201)->assertJson(['success' => true, 'data' => ['type' => 'receipt']]);

        $this->assertDatabaseHas('payment_receipts', ['amount' => 250.00, 'notes' => 'initial']);
    }

    /** B1: update actually saves the notes text (web used the wrong field). */
    public function test_update_saves_notes(): void
    {
        $receipt = PaymentReceipt::factory()->create(['notes' => null]);

        $this->putJson("/api/payment-receipts/{$receipt->id}", [
            'date'   => now()->toDateTimeString(),
            'type'   => $receipt->type,
            'amount' => 99.00,
            'notes'  => 'updated note',
        ])->assertOk();

        $this->assertDatabaseHas('payment_receipts', ['id' => $receipt->id, 'notes' => 'updated note']);
    }
}
