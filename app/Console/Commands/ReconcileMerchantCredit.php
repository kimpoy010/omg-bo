<?php

namespace App\Console\Commands;

use App\Models\MerchantCreditWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// The merchant credit is now updated in real time by Omg\CallbackController
// (each bet deducts, each win/refund/bonus adds, and a bet the credit can't
// cover is declined). This command is only a safety net that folds in any
// transactions the wallet hasn't counted — see MerchantCreditService::applyBacklog.
// Money is negative for debits, positive for credits, so delta = +SUM(money).
class ReconcileMerchantCredit extends Command
{
    protected $signature   = 'backoffice:reconcile-merchant-credit';
    protected $description = 'Adjust the merchant credit balance for bet/win activity since the last run';

    public function handle(): int
    {
        // The OMG callback now updates the credit per bet/win; this only folds in
        // anything left uncounted (e.g. rows from before that change). Safe to
        // run on a cron or by hand — it is idempotent.
        DB::transaction(function () {
            $wallet = MerchantCreditWallet::current();
            $before = (float) $wallet->balance;

            app(\App\Services\MerchantCreditService::class)->applyBacklog($wallet);

            $wallet->refresh();
            $this->info('Merchant credit: ' . number_format($before, 2) . ' → ' . number_format((float) $wallet->balance, 2));
        });

        return self::SUCCESS;
    }
}
