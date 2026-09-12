# Pago Móvil C2P — Integración de Pagos (Demo)

API en Laravel que simula la integración de un flujo de **Pago Móvil (C2P)** para comercios: validación de referencia bancaria, registro de transacción, confirmación por webhook y consulta de estado.

> ⚠️ Este repositorio es una **demostración con fines de portafolio**. Usa un gateway simulado (`FakePagoMovilGateway`) en vez de conectarse a un banco real, y no contiene datos ni lógica de ningún cliente. La arquitectura refleja el patrón real usado en integraciones de pago móvil en producción (Venezuela: C2P / UbiiPOS).

## ¿Qué resuelve este patrón?

En comercios que aceptan Pago Móvil, el flujo típico es:

1. El cliente paga desde su banco y genera una **referencia** (los últimos dígitos del número de confirmación).
2. El comercio valida esa referencia contra el banco o el gateway de pagos.
3. Se registra la transacción como `pending`.
4. El gateway confirma (o rechaza) el pago vía **webhook**, de forma asíncrona.
5. La transacción pasa a `confirmed` o `failed` — de forma **idempotente**, para que reintentos del webhook no dupliquen el estado.

## Arquitectura

```
app/
├── Models/Transaction.php                          # Modelo + estados de la transacción
├── Services/PagoMovil/
│   ├── Contracts/PaymentGatewayInterface.php        # Contrato — permite cambiar de gateway sin tocar el resto del código
│   ├── C2PPaymentService.php                        # Lógica de negocio: validar, registrar, confirmar
│   └── Exceptions/PaymentValidationException.php
├── Http/
│   ├── Requests/ValidatePaymentRequest.php           # Validación de entrada (Form Request)
│   └── Controllers/Api/PaymentController.php         # Endpoints REST
database/migrations/..._create_transactions_table.php
routes/api.php
tests/Feature/PaymentControllerTest.php
```

**Decisiones de diseño a propósito, para mostrar criterio, no solo sintaxis:**

- **Interface `PaymentGatewayInterface`** en vez de acoplar el servicio directo al gateway — en producción real esto permite cambiar de proveedor (C2P → UbiiPOS → otro) sin reescribir la lógica de negocio.
- **Servicio separado del controlador** — el controlador solo orquesta HTTP, la lógica de validación/estado vive en `C2PPaymentService`.
- **Idempotencia en el webhook** — un mismo evento de confirmación puede llegar más de una vez (reintentos del proveedor); el servicio no debe duplicar ni corromper el estado.
- **Excepción de dominio propia** (`PaymentValidationException`) en vez de dejar que un error genérico llegue al cliente.

## Endpoints

| Método | Ruta                         | Descripción                                  |
|--------|------------------------------|-----------------------------------------------|
| POST   | `/api/payments`              | Registra un intento de pago con su referencia |
| POST   | `/api/payments/webhook`      | Recibe la confirmación del gateway            |
| GET    | `/api/payments/{reference}`  | Consulta el estado de una transacción         |

## Stack

Laravel 11 · PHP 8.2 · MySQL/MariaDB

## Instalar (si quieres correrlo)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test
```
