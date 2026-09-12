<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidatePaymentRequest;
use App\Models\Transaction;
use App\Services\PagoMovil\C2PPaymentService;
use App\Services\PagoMovil\Exceptions\PaymentValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly C2PPaymentService $paymentService
    ) {
    }

    public function store(ValidatePaymentRequest $request): JsonResponse
    {
        try {
            $transaction = $this->paymentService->register(
                reference: $request->string('reference'),
                payerPhone: $request->string('payer_phone'),
                bankCode: $request->string('bank_code'),
                amount: (float) $request->input('amount'),
            );
        } catch (PaymentValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'reference' => $transaction->reference,
            'status' => $transaction->status,
        ], 201);
    }

    public function webhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string'],
            'approved' => ['required', 'boolean'],
        ]);

        $transaction = $this->paymentService->handleWebhook(
            reference: $validated['reference'],
            approved: $validated['approved'],
            rawPayload: $request->all(),
        );

        return response()->json([
            'reference' => $transaction->reference,
            'status' => $transaction->status,
        ]);
    }

    public function show(string $reference): JsonResponse
    {
        $transaction = Transaction::where('reference', $reference)->firstOrFail();

        return response()->json([
            'reference' => $transaction->reference,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'confirmed_at' => $transaction->confirmed_at,
        ]);
    }
}
