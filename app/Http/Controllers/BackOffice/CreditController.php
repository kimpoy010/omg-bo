<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\MerchantCreditLog;
use App\Models\MerchantCreditWallet;
use Illuminate\View\View;

// Read-only on purpose: only the webmaster can load or deduct merchant
// credit (see Webmaster\MerchantCreditController). The scheduled
// reconciliation command is the only other thing that changes the balance.
class CreditController extends Controller
{
    public function index(): View
    {
        $wallet = MerchantCreditWallet::get();
        $logs   = MerchantCreditLog::with(['admin', 'webmaster'])->latest()->paginate(25);

        return view('backoffice.credits.index', compact('wallet', 'logs'));
    }
}
