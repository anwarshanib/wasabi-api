<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles admin login / logout.
 *
 * Credentials are stored in .env:
 *   ADMIN_EMAIL=admin@example.com
 *   ADMIN_PASSWORD=your_secure_password
 *
 * No database user required — single admin account only.
 */
final class AdminAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (request()->session()->get('admin_authenticated')) {
            return redirect()->route('admin.tokens.index');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $validEmail    = (string) config('admin.email');
        $validPassword = (string) config('admin.password');

        if ($request->email !== $validEmail || $request->password !== $validPassword) {
            return back()
                ->withErrors(['email' => 'Invalid email or password.'])
                ->withInput(['email' => $request->email]);
        }

        $request->session()->put('admin_authenticated', true);
        $request->session()->regenerate();

        return redirect()->route('admin.tokens.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_authenticated');
        $request->session()->regenerate();

        return redirect()->route('admin.login');
    }
}
