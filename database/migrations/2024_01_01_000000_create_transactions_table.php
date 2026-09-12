<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Referencia entregada por el pagador (ej. últimos 6 dígitos del comprobante bancario).
            $table->string('reference', 20)->unique();

            $table->string('payer_phone', 20);
            $table->string('payer_bank_code', 4);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('VES');

            // pending -> confirmed | failed
            $table->string('status', 20)->default('pending');

            // Payload crudo del webhook, útil para auditoría y debugging.
            $table->json('gateway_response')->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
