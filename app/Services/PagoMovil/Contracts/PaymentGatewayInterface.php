<?php

namespace App\Services\PagoMovil\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Valida una referencia de pago contra el gateway/banco.
     *
     * @throws \App\Services\PagoMovil\Exceptions\PaymentValidationException
     */
    public function validateReference(string $reference, string $payerPhone, string $bankCode, float $amount): array;
}
