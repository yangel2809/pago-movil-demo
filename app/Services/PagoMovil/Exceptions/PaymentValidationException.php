<?php

namespace App\Services\PagoMovil\Exceptions;

use Exception;

class PaymentValidationException extends Exception
{
    public static function referenceNotFound(string $reference): self
    {
        return new self("La referencia {$reference} no fue encontrada en el gateway de pago.");
    }

    public static function amountMismatch(string $reference): self
    {
        return new self("El monto reportado para la referencia {$reference} no coincide con el esperado.");
    }

    public static function duplicateReference(string $reference): self
    {
        return new self("La referencia {$reference} ya fue registrada anteriormente.");
    }
}
