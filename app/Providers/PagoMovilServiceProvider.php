<?php

namespace App\Providers;

use App\Services\PagoMovil\Contracts\PaymentGatewayInterface;
use App\Services\PagoMovil\FakePagoMovilGateway;
use Illuminate\Support\ServiceProvider;

class PagoMovilServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // En producción, este binding se cambiaría por el gateway real
        // (ej. UbiiPosGateway) sin tocar C2PPaymentService ni el controlador.
        $this->app->bind(PaymentGatewayInterface::class, FakePagoMovilGateway::class);
    }
}
