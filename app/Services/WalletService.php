<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public const METHODS = ['card', 'kaspi', 'apple_pay', 'google_pay'];

    public function topUp(User $user, float $amount, string $method): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $method) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $balance = round((float) $locked->balance + $amount, 2);
            $locked->forceFill(['balance' => $balance])->save();
            $user->forceFill(['balance' => $balance])->syncOriginal();

            $tx = $locked->walletTransactions()->create([
                'type' => 'topup',
                'amount' => $amount,
                'balance_after' => $balance,
                'method' => $method,
                'description' => 'Пополнение баланса',
            ]);
            Log::info('Пополнение баланса', ['user_id' => $user->id, 'amount' => $amount, 'method' => $method]);

            return $tx;
        });
    }

    public function charge(User $user, float $amount, string $description): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ((float) $locked->balance < $amount) {
                abort(422, 'Недостаточно средств на балансе. Пополните баланс.');
            }

            $balance = round((float) $locked->balance - $amount, 2);
            $locked->forceFill(['balance' => $balance])->save();
            $user->forceFill(['balance' => $balance])->syncOriginal();

            $tx = $locked->walletTransactions()->create([
                'type' => 'purchase',
                'amount' => -$amount,
                'balance_after' => $balance,
                'description' => $description,
            ]);
            Log::info('Списание с баланса', ['user_id' => $user->id, 'amount' => $amount]);

            return $tx;
        });
    }
}