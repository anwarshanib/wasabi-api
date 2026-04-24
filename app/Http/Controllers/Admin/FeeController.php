<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeLedger;
use App\Models\FeeSetting;
use App\Models\PlatformSetting;
use App\Services\WasabiCard\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FeeController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    public function index(): View
    {
        $feeSettings        = FeeSetting::all()->keyBy('fee_type');
        $wasabiAccounts     = $this->accountService->getAssets();
        $currentSource      = PlatformSetting::get(PlatformSetting::KEY_FEE_SOURCE);
        $currentDestination = PlatformSetting::get(PlatformSetting::KEY_FEE_DESTINATION);
        $ledgerRows         = FeeLedger::with('apiToken')->latest()->paginate(20);

        return view('admin.fees.index', compact(
            'feeSettings',
            'wasabiAccounts',
            'currentSource',
            'currentDestination',
            'ledgerRows',
        ));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'deposit_rate'          => ['required', 'numeric', 'min:0', 'max:100'],
            'deposit_active'        => ['nullable', 'boolean'],
            'card_application_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'card_application_active' => ['nullable', 'boolean'],
            'fx_rate'               => ['required', 'numeric', 'min:0', 'max:100'],
            'fx_active'             => ['nullable', 'boolean'],
        ]);

        $types = [
            FeeSetting::TYPE_DEPOSIT          => ['rate' => $validated['deposit_rate'],          'active' => $request->boolean('deposit_active')],
            FeeSetting::TYPE_CARD_APPLICATION => ['rate' => $validated['card_application_rate'], 'active' => $request->boolean('card_application_active')],
            FeeSetting::TYPE_FX               => ['rate' => $validated['fx_rate'],               'active' => $request->boolean('fx_active')],
        ];

        foreach ($types as $feeType => $values) {
            FeeSetting::updateOrCreate(
                ['fee_type' => $feeType],
                ['rate' => $values['rate'], 'is_active' => $values['active']],
            );
        }

        return back()->with('success', 'Fee settings saved.');
    }

    public function createWallet(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $this->accountService->createSharedAccount($validated['name']);

        return back()->with('success', "Wallet \"{$validated['name']}\" created.");
    }

    public function setSourceAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string'],
        ]);

        PlatformSetting::set(PlatformSetting::KEY_FEE_SOURCE, $validated['account_id']);

        return back()->with('success', 'Fee source account updated.');
    }

    public function setDestinationAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string'],
        ]);

        PlatformSetting::set(PlatformSetting::KEY_FEE_DESTINATION, $validated['account_id']);

        return back()->with('success', 'Fee destination account updated.');
    }
}
