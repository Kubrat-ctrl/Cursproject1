<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopUpRequest;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class WalletController extends Controller
{
    public function __construct(private readonly WalletService $wallet)
    {
    }

    /**
     * Баланс и история операций
     *
     * Доступ: клиент.
     *
     * @group Баланс
     * @authenticated
     * @response 200 {"balance": "5000.00", "transactions": [{"id": 1, "type": "topup", "amount": "5000.00", "balance_after": "5000.00", "method": "card", "description": "Пополнение баланса", "created_at": "2026-11-12T11:00:00.000000Z"}]}
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->fresh();

        return response()->json([
            'balance' => $user->balance,
            'transactions' => $user->walletTransactions()
                ->latest('id')->limit(50)
                ->get(['id', 'type', 'amount', 'balance_after', 'method', 'description', 'created_at']),
        ]);
    }

    /**
     * Пополнение баланса (имитация оплаты, реальные деньги не списываются)
     *
     * Тестовые карты: 4242 4242 4242 4242 (успех), 4000 0000 0000 0002 (отказ банка).
     *
     * @group Баланс
     * @authenticated
     * @bodyParam amount number required Сумма от 100 до 500000. Example: 5000
     * @bodyParam method string required card, kaspi, apple_pay или google_pay. Example: card
     * @bodyParam card_number string Номер карты (только для card). Example: 4242424242424242
     * @bodyParam card_expiry string Срок MM/YY (только для card). Example: 12/30
     * @bodyParam card_cvc string CVC (только для card). Example: 123
     * @response 201 {"balance": "5000.00", "transaction": {"id": 1, "type": "topup", "amount": "5000.00"}}
     * @response 422 {"message": "Платёж отклонён банком"}
     */
    public function topUp(TopUpRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($data['method'] === 'card') {
            $this->checkCard($data);
        }

        $tx = $this->wallet->topUp($request->user(), (float) $data['amount'], $data['method']);

        return response()->json([
            'balance' => $tx->balance_after,
            'transaction' => $tx,
        ], 201);
    }

    /** Проверка тестовой карты. Данные карты нигде не сохраняются. */
    private function checkCard(array $data): void
    {
        $expiry = Carbon::createFromFormat('!m/y', $data['card_expiry'])->endOfMonth();
        if ($expiry->isPast()) {
            abort(422, 'Срок действия карты истёк');
        }
        if ($data['card_number'] === '4000000000000002') {
            abort(422, 'Платёж отклонён банком');
        }
        if (! $this->luhn($data['card_number'])) {
            abort(422, 'Неверный номер карты');
        }
    }

    private function luhn(string $number): bool
    {
        $sum = 0;
        foreach (array_reverse(str_split($number)) as $i => $digit) {
            $d = (int) $digit;
            if ($i % 2 === 1) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
        }

        return $sum % 10 === 0;
    }
}