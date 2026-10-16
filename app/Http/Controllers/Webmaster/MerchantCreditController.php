<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Models\MerchantCreditLog;
use App\Models\MerchantCreditWallet;
use App\Services\MerchantCreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Webmaster-only. Loading credit after a client buys it is a sale, so the
// merchant can't do it themselves from the casino back office.
class MerchantCreditController extends Controller
{
    public function index(): View
    {
        return view('webmaster.merchant-credits.index', [
            'wallet' => MerchantCreditWallet::get(),
            'logs'   => MerchantCreditLog::with(['webmaster', 'admin'])->latest()->paginate(25),
        ]);
    }

    public function topup(Request $request, MerchantCreditService $credits): RedirectResponse
    {
        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:1000000000'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $credits->adjust('topup', (float) $data['amount'], $data['description'] ?? null, auth()->id());

        return back()->with('success', '₱' . number_format((float) $data['amount'], 2) . ' added to merchant credit.');
    }

    public function deduct(Request $request, MerchantCreditService $credits): RedirectResponse
    {
        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:1000000000'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $credits->adjust('deduction', -(float) $data['amount'], $data['description'] ?? null, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return back()->with('success', '₱' . number_format((float) $data['amount'], 2) . ' deducted from merchant credit.');
    }

    public function reset(Request $request, MerchantCreditService $credits): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'in:RESET']], ['confirm.in' => 'Type RESET to confirm.']);

        $credits->reset(auth()->id());

        return back()->with('success', 'Merchant reset. Balance, logs and reports now start from zero.');
    }
}
