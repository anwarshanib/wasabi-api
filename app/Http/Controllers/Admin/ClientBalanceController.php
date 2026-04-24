<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\ClientBalance;
use App\Models\ClientTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ClientBalanceController extends Controller
{
    /**
     * Balance overview — one row per third-party client showing their
     * current balance and summary of activity.
     */
    public function index()
    {
        $clients = ApiToken::query()
            ->with(['clientBalance'])
            ->orderBy('name')
            ->get()
            ->map(function (ApiToken $token): array {
                $balance = $token->clientBalance;

                $totalDeposited = ClientTransaction::where('api_token_id', $token->id)
                    ->where('event', ClientTransaction::EVENT_DEPOSIT)
                    ->where('status', ClientTransaction::STATUS_CONFIRMED)
                    ->where('type', 'credit')
                    ->sum('amount');

                $totalFeesPaid = ClientTransaction::where('api_token_id', $token->id)
                    ->where('event', ClientTransaction::EVENT_PLATFORM_FEE)
                    ->where('status', ClientTransaction::STATUS_CONFIRMED)
                    ->sum('amount');

                $totalSpent = ClientTransaction::where('api_token_id', $token->id)
                    ->whereIn('event', [
                        ClientTransaction::EVENT_CARD_CREATE,
                        ClientTransaction::EVENT_CARD_DEPOSIT,
                    ])
                    ->where('status', ClientTransaction::STATUS_CONFIRMED)
                    ->where('type', 'debit')
                    ->sum('amount');

                $lastDeposit = ClientTransaction::where('api_token_id', $token->id)
                    ->where('event', ClientTransaction::EVENT_DEPOSIT)
                    ->latest()
                    ->first();

                return [
                    'token'           => $token,
                    'balance'         => $balance?->balance ?? '0.0000',
                    'currency'        => $balance?->currency ?? 'USD',
                    'total_deposited' => $totalDeposited,
                    'total_fees_paid' => $totalFeesPaid,
                    'total_spent'     => $totalSpent,
                    'last_deposit_at' => $lastDeposit?->created_at,
                ];
            });

        return view('admin.clients.balances', ['clients' => $clients]);
    }

    /**
     * Full transaction ledger for a single client, filterable by date and event type.
     */
    public function transactions(Request $request, ApiToken $token)
    {
        $query = ClientTransaction::where('api_token_id', $token->id)
            ->orderByDesc('created_at');

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $transactions = $query->paginate(50)->withQueryString();

        $balance = ClientBalance::where('api_token_id', $token->id)->first();

        $eventTypes = [
            ClientTransaction::EVENT_DEPOSIT,
            ClientTransaction::EVENT_CARD_CREATE,
            ClientTransaction::EVENT_CARD_DEPOSIT,
            ClientTransaction::EVENT_CARD_WITHDRAW,
            ClientTransaction::EVENT_CARD_CANCEL_REFUND,
            ClientTransaction::EVENT_PLATFORM_FEE,
            ClientTransaction::EVENT_AUTH_FEE_PATCH,
            ClientTransaction::EVENT_CROSS_BORDER_FEE,
            ClientTransaction::EVENT_OVERDRAFT,
            ClientTransaction::EVENT_ADJUSTMENT,
        ];

        return view('admin.clients.transactions', compact('token', 'transactions', 'balance', 'eventTypes'));
    }

    /**
     * Export client transactions as CSV.
     */
    public function exportCsv(Request $request, ApiToken $token): Response
    {
        $query = ClientTransaction::where('api_token_id', $token->id)
            ->orderByDesc('created_at');

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $rows = $query->get();

        $csv  = "Date,Type,Event,Amount,Balance Before,Balance After,Reference ID,Status\n";
        foreach ($rows as $row) {
            $csv .= implode(',', [
                $row->created_at->format('Y-m-d H:i:s'),
                $row->type,
                $row->event,
                $row->amount,
                $row->balance_before,
                $row->balance_after,
                $row->reference_id ?? '',
                $row->status,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$token->name}_ledger.csv\"",
        ]);
    }
}
