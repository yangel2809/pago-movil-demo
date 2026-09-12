<?php

namespace Tests\Feature;

use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_a_valid_payment_as_pending(): void
    {
        $response = $this->postJson('/api/payments', [
            'reference' => '123456',
            'payer_phone' => '04141234567',
            'bank_code' => '0102',
            'amount' => 50.00,
        ]);

        $response->assertStatus(201)
            ->assertJson(['reference' => '123456', 'status' => 'pending']);

        $this->assertDatabaseHas('transactions', [
            'reference' => '123456',
            'status' => 'pending',
        ]);
    }

    public function test_rejects_reference_not_found_in_gateway(): void
    {
        $response = $this->postJson('/api/payments', [
            'reference' => '012345', // empieza en 0 -> el fake gateway la rechaza
            'payer_phone' => '04141234567',
            'bank_code' => '0102',
            'amount' => 50.00,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('transactions', ['reference' => '012345']);
    }

    public function test_rejects_duplicate_reference(): void
    {
        Transaction::factory()->create(['reference' => '999999']);

        $response = $this->postJson('/api/payments', [
            'reference' => '999999',
            'payer_phone' => '04141234567',
            'bank_code' => '0102',
            'amount' => 50.00,
        ]);

        $response->assertStatus(422);
    }

    public function test_webhook_confirms_a_pending_transaction(): void
    {
        $transaction = Transaction::factory()->create([
            'reference' => '555555',
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/payments/webhook', [
            'reference' => '555555',
            'approved' => true,
        ]);

        $response->assertOk()->assertJson(['status' => 'confirmed']);

        $this->assertNotNull($transaction->fresh()->confirmed_at);
    }

    public function test_webhook_is_idempotent_on_repeated_calls(): void
    {
        Transaction::factory()->create([
            'reference' => '555555',
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $response = $this->postJson('/api/payments/webhook', [
            'reference' => '555555',
            'approved' => true,
        ]);

        // No debe fallar ni cambiar el estado al reprocesar un evento repetido.
        $response->assertOk()->assertJson(['status' => 'confirmed']);
    }
}
