<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'reference',
        'payer_phone',
        'payer_bank_code',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'confirmed_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markConfirmed(array $gatewayResponse): void
    {
        // Idempotente: si ya estaba confirmada, no hace nada (evita reprocesar webhooks duplicados).
        if ($this->status === self::STATUS_CONFIRMED) {
            return;
        }

        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'gateway_response' => $gatewayResponse,
            'confirmed_at' => now(),
        ]);
    }

    public function markFailed(array $gatewayResponse): void
    {
        if ($this->status === self::STATUS_FAILED) {
            return;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'gateway_response' => $gatewayResponse,
        ]);
    }
}
