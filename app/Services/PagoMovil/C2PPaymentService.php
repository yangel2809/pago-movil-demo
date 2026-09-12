<?php

namespace App\Services\PagoMovil;

use App\Models\Transaction;
use App\Services\PagoMovil\Contracts\PaymentGatewayInterface;
use App\Services\PagoMovil\Exceptions\PaymentValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class C2PPaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway
    ) {
    }

    /**
     * Registra un intento de pago: valida contra el gateway y crea la
     * transacción en estado "pending" hasta que llegue la confirmación
     * por webhook.
     *
     * @throws PaymentValidationException
     */
    public function register(string $reference, string $payerPhone, string $bankCode, float $amount): Transaction
    {
        if (Transaction::where('reference', $reference)->exists()) {
            throw PaymentValidationException::duplicateReference($reference);
        }

        $gatewayResult = $this->gateway->validateReference($reference, $payerPhone, $bankCode, $amount);

        if ((float) $gatewayResult['amount'] !== $amount) {
            throw PaymentValidationException::amountMismatch($reference);
        }

        return Transaction::create([
            'reference' => $reference,
            'payer_phone' => $payerPhone,
            'payer_bank_code' => $bankCode,
            'amount' => $amount,
            'status' => Transaction::STATUS_PENDING,
            'gateway_response' => $gatewayResult,
        ]);
    }

    /**
     * Procesa la confirmación asíncrona del gateway (webhook).
     *
     * Idempotente a propósito: el mismo evento puede llegar más de una vez
     * (reintentos de red del proveedor) y no debe romper ni duplicar estado.
     */
    public function handleWebhook(string $reference, bool $approved, array $rawPayload): Transaction
    {
        return DB::transaction(function () use ($reference, $approved, $rawPayload) {
            /** @var Transaction $transaction */
            $transaction = Transaction::where('reference', $reference)->lockForUpdate()->firstOrFail();

            if (!$transaction->isPending()) {
                Log::info('Webhook recibido para transacción ya procesada, se ignora.', [
                    'reference' => $reference,
                    'current_status' => $transaction->status,
                ]);

                return $transaction;
            }

            $approved
                ? $transaction->markConfirmed($rawPayload)
                : $transaction->markFailed($rawPayload);

            return $transaction->refresh();
        });
    }
}
