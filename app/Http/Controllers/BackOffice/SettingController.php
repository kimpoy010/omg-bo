<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\BackOfficeOperationLog;
use App\Models\MerchantCallbackUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Where the merchant registers the callback URLs OMG should call. Stored
// only — this app never requests them.
class SettingController extends Controller
{
    public function index(): View
    {
        return view('backoffice.settings.index', [
            'urls'   => MerchantCallbackUrl::get(),
            'fields' => MerchantCallbackUrl::FIELDS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (MerchantCallbackUrl::FIELDS as $field => $label) {
            $rules[$field] = ['nullable', 'string', 'max:500', 'url:https'];
        }

        $data = $request->validate($rules, ['*.url' => 'The :attribute must be a valid https:// URL.']);

        $urls = MerchantCallbackUrl::get();
        $urls->fill(array_map(fn ($v) => $v === null || $v === '' ? null : trim($v), $data));
        $urls->updated_by = Auth::id();

        $changed = array_keys($urls->getDirty());
        $urls->save();

        if ($changed !== []) {
            BackOfficeOperationLog::record('callback_urls', 'Updated: ' . implode(', ', array_diff($changed, ['updated_by'])));
        }

        return back()->with('success', 'Callback URLs saved.');
    }
}
