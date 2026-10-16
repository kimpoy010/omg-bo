<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BackOfficeOperationLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['username' => 'Invalid username or password.'])->onlyInput('username');
        }

        // Only the two staff roles may sign in.
        if (! Auth::user()->hasAnyRole([User::ROLE_WEBMASTER, User::ROLE_MERCHANT])) {
            Auth::logout();

            return back()->withErrors(['username' => 'This account has no access.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        if (Auth::user()->hasRole(User::ROLE_MERCHANT)) {
            BackOfficeOperationLog::record('login', 'Signed in');
        }

        return redirect()->route('home');
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::user()?->hasRole(User::ROLE_MERCHANT)) {
            BackOfficeOperationLog::record('logout', 'Signed out');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // Send each role to its own section.
    public function home(): RedirectResponse
    {
        return Auth::user()->hasRole(User::ROLE_WEBMASTER)
            ? redirect()->route('webmaster.merchant-credits.index')
            : redirect()->route('backoffice.dashboard');
    }
}
