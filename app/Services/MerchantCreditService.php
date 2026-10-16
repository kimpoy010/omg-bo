<?php

namespace App\Services;

use App\Models\MerchantCreditLog;
use App\Models\MerchantCreditWallet;
use App\Models\OmgTransaction;
use App\Models\BackOfficeOperationLog;
use Illuminate\Support\Facades\DB;

// The only place merchant credit is manually changed. Called from the
// webmaster panel — the merchant (back office) itself can only view it.
class MerchantCreditService
{
    public function adjust(string $type, float $signedAmount, ?string $description, int $webmasterId): MerchantCreditLog
    {
        return DB::transaction(function () use ($type, $signedAmount, $description, $webmasterId) {
            $wallet = MerchantCreditWallet::current();

            if ($wallet->balance + $signedAmount < 0) {
                throw new \RuntimeException('Deduction is larger than the current merchant credit balance.');
            }

            $wallet->increment('balance', $signedAmount);
            $wallet->refresh();

            return MerchantCreditLog::create([
                'type'          => $type,
                'amount'        => $signedAmount,
                'balance_after' => $wallet->balance,
                'webmaster_id'  => $webmasterId,
                'description'   => $description,
            ]);
        });
    }

    // Fresh start for the merchant: balance to zero, credit and operation
    // logs wiped, and every report restarted from the current transaction.
    // omg_transactions itself is untouched (player history, VIP).
    public function reset(int $webmasterId): void
    {
        DB::transaction(function () use ($webmasterId) {
            $wallet = MerchantCreditWallet::current();
            $maxId  = (int) OmgTransaction::max('id');

            $wallet->update([
                'balance'                        => 0,
                'last_reconciled_transaction_id' => $maxId,
                'last_reconciled_at'             => null,
                'reset_transaction_id'           => $maxId,
                'reset_at'                       => now(),
                'reset_by'                       => $webmasterId,
            ]);

            MerchantCreditLog::query()->delete();
            BackOfficeOperationLog::query()->delete();
        });
    }

    /**
     * Fold into $wallet any casino transactions the wallet hasn't counted yet
     * (id above its high-water mark). Callers must hold the wallet row lock.
     * Normally empty — the OMG callback keeps the balance current per bet — so
     * this mostly catches up legacy rows left by the old periodic reconcile.
     * Bets deduct, wins/refunds/bonuses add: delta = +SUM(money).
     */
    public function applyBacklog(MerchantCreditWallet $wallet): void
    {
        $query = OmgTransaction::where('id', '>', $wallet->last_reconciled_transaction_id)->where('money', '!=', 0);
        $maxId = (int) (clone $query)->max('id');

        if ($maxId === 0) {
            return;
        }

        $delta = (float) (clone $query)->sum('money');

        $wallet->update([
            'balance'                        => (float) $wallet->balance + $delta,
            'last_reconciled_transaction_id' => $maxId,
            'last_reconciled_at'             => now(),
        ]);

        MerchantCreditLog::create([
            'type'          => 'reconciliation',
            'amount'        => $delta,
            'balance_after' => $wallet->balance,
            'description'   => "Transactions up to #{$maxId}",
        ]);
    }
}
