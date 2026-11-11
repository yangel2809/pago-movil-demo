# Handover — pago-movil-demo

> Última actualización: 2026-11-11 · Rama por defecto: `main`

## 1. Resumen
API Laravel que simula un flujo de **Pago Móvil C2P** (Venezuela): registro de un pago por referencia bancaria → transacción `pending` → confirmación/rechazo asíncrono por webhook idempotente → consulta de estado. Usa un gateway falso (`FakePagoMovilGateway`); el README la declara **demo de portafolio** sin datos de cliente. Estado: demo, 1 commit (2026-09-12).

**Importante:** el repo contiene solo el código de dominio, **no un esqueleto Laravel completo** (ver §4 y §11). No arranca tal cual.

## 2. Stack
- PHP ^8.2, `laravel/framework` ^11.0.
- Dev: `phpunit/phpunit` ^11, `fakerphp/faker` ^1.23.
- BD: MySQL/MariaDB según `.env.example` (`DB_CONNECTION=mysql`).
- No hay `composer.lock`.

## 3. Estructura del repositorio
```
app/Http/Controllers/Api/PaymentController.php     # store, webhook, show
app/Http/Requests/ValidatePaymentRequest.php       # reglas: reference size:6, payer_phone /^04\d{9}$/, bank_code size:4, amount ≥ 0.01
app/Models/Transaction.php                         # estados pending/confirmed/failed, markConfirmed/markFailed idempotentes
app/Services/PagoMovil/C2PPaymentService.php       # register(), handleWebhook() (DB::transaction + lockForUpdate)
app/Services/PagoMovil/Contracts/PaymentGatewayInterface.php
app/Services/PagoMovil/FakePagoMovilGateway.php    # referencias que empiezan por "0" → no encontradas
app/Services/PagoMovil/Exceptions/PaymentValidationException.php
app/Providers/PagoMovilServiceProvider.php         # bind PaymentGatewayInterface → FakePagoMovilGateway
database/migrations/2024_01_01_000000_create_transactions_table.php
database/factories/TransactionFactory.php
routes/api.php
tests/Feature/PaymentControllerTest.php            # 5 tests de feature (RefreshDatabase)
```

## 4. Puesta en marcha local
Faltan del repo: `artisan`, `bootstrap/` (app.php, providers.php), `config/`, `public/`, `app/Http/Controllers/Controller.php`, `tests/TestCase.php`, `phpunit.xml`. Los comandos del README (`php artisan ...`) fallan sin ellos. Procedimiento sugerido:
```bash
composer create-project laravel/laravel:^11.0 tmp-skeleton
# copiar encima de tmp-skeleton: app/, database/, routes/api.php, tests/Feature/, .env.example, composer.json (fusionar)
php artisan install:api            # habilita routes/api.php en Laravel 11 (prefijo /api)
# registrar App\Providers\PagoMovilServiceProvider en bootstrap/providers.php
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan test
```
> Por confirmar: no se ha probado el procedimiento; no se ejecutaron los tests en este traspaso.

## 5. Configuración y variables de entorno
Archivo de referencia: `.env.example` (no contiene secretos reales).

| Variable | Propósito |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL` | Config estándar Laravel |
| `APP_KEY` | Clave de cifrado (generar con `key:generate`) |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexión MySQL |

No hay variables para un gateway real (URL, credenciales, secreto de webhook): no existen aún.

## 6. Base de datos
Motor MySQL/MariaDB (tests: lo que defina `phpunit.xml`, ausente). Esquema por migración única:
- `transactions`: `reference` (string 20, unique), `payer_phone`, `payer_bank_code` (4), `amount` decimal(12,2), `currency` (default `VES`), `status` (default `pending`, indexado), `gateway_response` JSON, `confirmed_at`, timestamps.
Factory: `database/factories/TransactionFactory.php`.

## 7. Despliegue
No hay Dockerfile, CI ni scripts de despliegue. No se infiere entorno de producción.

## 8. Integraciones externas
Ninguna real. Punto de extensión: implementar `PaymentGatewayInterface` (p. ej. el README y comentarios mencionan UbiiPOS) y cambiar el binding en `PagoMovilServiceProvider`.

## 9. Funcionalidades principales
| Método | Ruta | Comportamiento |
|---|---|---|
| POST | `/api/payments` | Valida request, rechaza referencia duplicada, valida contra gateway, verifica monto, crea `pending` → 201; error de dominio → 422 |
| POST | `/api/payments/webhook` | Body `reference`, `approved` (bool). Si ya no está `pending`, lo ignora (idempotente) |
| GET | `/api/payments/{reference}` | Estado, monto, `confirmed_at`; 404 si no existe |

## 10. Estado actual y pendientes
- Commit único "first commit" (2026-09-12). Sin TODO/FIXME.
- Pendiente: completar esqueleto Laravel para que corra, gateway real, autenticación del webhook.

## 11. Riesgos y deuda técnica
- **Repo no ejecutable**: falta el esqueleto Laravel (ver §4); `PagoMovilServiceProvider` no está registrado en ningún sitio, y `Controller` / `Tests\TestCase` referenciados no existen.
- **Webhook sin autenticación** (`PaymentController::webhook`): cualquiera puede confirmar un pago enviando `reference` + `approved=true`. Falta verificación de firma/secreto/IP allowlist.
- `ValidatePaymentRequest` exige referencia de 6 caracteres pero no que sea numérica; la columna admite 20.
- Comparación de montos con `float` (`C2PPaymentService::register`); para dinero conviene comparar en céntimos/decimal.
- Sin `composer.lock`: instalaciones no reproducibles. Sin CI.
- Rutas sin rate limiting ni auth.

## 12. Accesos y contactos
> Por confirmar: no hay servidores, credenciales ni cuentas de proveedor de pagos asociadas. Repo: `github.com/yangel2809/pago-movil-demo`. Responsable por confirmar.
