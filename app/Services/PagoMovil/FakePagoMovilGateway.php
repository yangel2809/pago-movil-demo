<?php

namespace App\Services\PagoMovil;

use App\Services\PagoMovil\Contracts\PaymentGatewayInterface;
use App\Services\PagoMovil\Exceptions\PaymentValidationException;

/**
 * Implementación simulada del gateway de Pago Móvil (C2P).
 *
 * En un entorno real, esta clase haría la llamada HTTP al banco o al proveedor
 * (ej. UbiiPOS) y traduciría su respuesta al mismo formato de array que se
 * devuelve aquí. El resto del sistema (C2PPaymentService, controlador, tests)
 * no necesita cambiar si mañana se reemplaza este gateway por uno real —
 * solo se implementa PaymentGatewayInterface de nuevo.
 */
class FakePagoMovilGateway implements PaymentGatewayInterface
{
    public function validateReference(string $reference, string $payerPhone, string $bankCode, float $amount): array
    {
        // Simulación: referencias que empiezan en "0" se consideran no encontradas,
        // para poder probar el camino de error fácilmente en el demo.
        if (str_starts_with($reference, '0')) {
            throw PaymentValidationException::referenceNotFound($reference);
        }

        return [
            'reference' => $reference,
            'payer_phone' => $payerPhone,
            'bank_code' => $bankCode,
            'amount' => $amount,
            'validated_at' => now()->toIso8601String(),
            'gateway' => 'fake-c2p',
        ];
    }
}
