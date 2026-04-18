<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for third-party API tokens.
 *
 * Operations:
 *   index   — list all tokens (paginated)
 *   create  — show create form
 *   store   — generate and persist a new token, flash raw value once
 *   toggle  — enable / disable a token
 *   reveal  — decrypt and display the raw token (admin only)
 *   destroy — permanently delete a token
 */
final class TokenController extends Controller
{
    public function index(): View
    {
        $tokens = ApiToken::latest()->paginate(20);

        return view('admin.tokens.index', compact('tokens'));
    }

    public function create(): View
    {
        return view('admin.tokens.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'email'       => ['nullable', 'email', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        [$rawToken, $token] = ApiToken::generateAndCreate(
            $validated['name'],
            $validated['email'] ?? null,
            $validated['description'] ?? null,
        );

        // Flash the raw token once — it cannot be recovered from the DB after this
        return redirect()
            ->route('admin.tokens.index')
            ->with('new_token', $rawToken)
            ->with('new_token_name', $token->name);
    }

    public function toggle(ApiToken $token): RedirectResponse
    {
        $token->update(['is_active' => ! $token->is_active]);

        $status = $token->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "Token \"{$token->name}\" has been {$status}.");
    }

    public function reveal(ApiToken $token): RedirectResponse
    {
        $rawToken = $token->decryptToken();

        return back()->with('revealed_token', $rawToken)->with('revealed_name', $token->name);
    }

    public function destroy(ApiToken $token): RedirectResponse
    {
        $name = $token->name;
        $token->delete();

        return redirect()->route('admin.tokens.index')
            ->with('success', "Token \"{$name}\" has been permanently deleted.");
    }
}
