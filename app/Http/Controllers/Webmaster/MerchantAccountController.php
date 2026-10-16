<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// The merchant login (one account per deployment). Only the webmaster can
// create or change it. Leave the password blank to keep the current one.
class MerchantAccountController extends Controller
{
    public function index(): View
    {
        return view('webmaster.merchant-account.index', ['merchant' => $this->merchant()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $merchant = $this->merchant();

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^\S+$/', Rule::unique('users', 'username')->ignore($merchant?->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:100', 'confirmed'],
        ], [
            'username.regex' => 'The username cannot contain spaces.',
        ]);

        if (! $merchant && empty($data['password'])) {
            return back()->withInput()->withErrors(['password' => 'Set a password to create the merchant account.']);
        }

        $merchant ??= new User();
        $merchant->username = $data['username'];

        if (! empty($data['password'])) {
            $merchant->password       = $data['password']; // hashed by the model cast
            $merchant->remember_token = Str::random(60);   // drop any "remember me" logins
        }

        $merchant->save();
        $merchant->syncRoles([User::ROLE_MERCHANT]);

        return back()->with('success', 'Merchant login updated.' . (! empty($data['password']) ? ' The new password is active now.' : ''));
    }

    private function merchant(): ?User
    {
        return User::role(User::ROLE_MERCHANT)->orderBy('id')->first();
    }
}
