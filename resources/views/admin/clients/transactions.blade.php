@extends('admin.layout')

@section('title', $token->name . ' — Ledger')

@section('content')

{{-- ── Page header ─────────────────────────────────────────────────────── --}}
<div class="page-header">
    <div>
        <a href="{{ route('admin.clients.balances') }}" style="color:#6b7280;font-size:13px;text-decoration:none">← Balances</a>
        <h1 class="page-title" style="margin-top:4px">{{ $token->name }} — Transaction Ledger</h1>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 20px;text-align:center">
            <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px">Current Balance</div>
            <div style="font-size:24px;font-weight:700;color:{{ (float)($balance?->balance ?? 0) > 0 ? '#059669' : '#6b7280' }}">
                ${{ number_format((float)($balance?->balance ?? 0), 4) }}
            </div>
        </div>
        <a href="{{ route('admin.clients.export', ['token' => $token, 'event' => request('event'), 'from' => request('from'), 'to' => request('to')]) }}"
           class="btn btn-secondary">Export CSV</a>
    </div>
</div>

{{-- ── Client info card ────────────────────────────────────────────────── --}}
<div style="background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.08);padding:20px 24px;margin-bottom:24px;display:flex;gap:32px;flex-wrap:wrap;align-items:center">
    <div style="display:flex;align-items:center;gap:14px">
        <div style="width:48px;height:48px;border-radius:50%;background:#e0f2fe;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px;color:#0369a1;flex-shrink:0">
            {{ strtoupper(substr($token->name, 0, 1)) }}
        </div>
        <div>
            <div style="font-size:16px;font-weight:700;color:#111827">{{ $token->name }}</div>
            <div style="font-size:13px;color:#6b7280;margin-top:2px">{{ $token->email ?? 'No email' }}</div>
            @if($token->description)
                <div style="font-size:12px;color:#9ca3af;margin-top:2px">{{ $token->description }}</div>
            @endif
        </div>
    </div>
    <div style="height:40px;width:1px;background:#e5e7eb"></div>
    <div>
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Client ID</div>
        <div style="font-size:14px;font-weight:600;color:#374151">#{{ $token->id }}</div>
    </div>
    <div>
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Member Since</div>
        <div style="font-size:14px;font-weight:600;color:#374151">{{ $token->created_at->format('d M Y') }}</div>
    </div>
    <div>
        <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Status</div>
        @if($token->is_active)
            <span style="background:#d1fae5;color:#065f46;font-size:12px;font-weight:600;padding:3px 10px;border-radius:999px">Active</span>
        @else
            <span style="background:#fee2e2;color:#991b1b;font-size:12px;font-weight:600;padding:3px 10px;border-radius:999px">Inactive</span>
        @endif
    </div>

    {{-- ── Platform fee highlight ── --}}
    @php
        $totalFees      = \App\Models\ClientTransaction::where('api_token_id',$token->id)->where('event','platform_fee')->where('status','confirmed')->sum('amount');
        $totalDeposited = \App\Models\ClientTransaction::where('api_token_id',$token->id)->where('event','deposit')->where('status','confirmed')->where('type','credit')->sum('amount');
    @endphp
    <div style="margin-left:auto;background:linear-gradient(135deg,#fef9c3,#fef3c7);border:1px solid #fde68a;border-radius:10px;padding:14px 22px;text-align:right;min-width:180px">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#92400e;margin-bottom:6px">
            💰 Platform Fees Collected
        </div>
        <div style="font-size:22px;font-weight:800;color:#b45309">${{ number_format((float)$totalFees, 4) }}</div>
        @if((float)$totalDeposited > 0)
            <div style="font-size:11px;color:#d97706;margin-top:4px">
                {{ number_format((float)$totalFees / (float)$totalDeposited * 100, 2) }}% effective rate on ${{ number_format((float)$totalDeposited, 2) }} deposited
            </div>
        @endif
    </div>
</div>

{{-- ── Filters ─────────────────────────────────────────────────────────── --}}
<form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:flex-end">
    <div>
        <label style="display:block;font-size:12px;color:#6b7280;margin-bottom:4px">Event Type</label>
        <select name="event" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
            <option value="">All Events</option>
            @foreach($eventTypes as $evt)
            <option value="{{ $evt }}" @selected(request('event') === $evt)>{{ str_replace('_', ' ', ucfirst($evt)) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label style="display:block;font-size:12px;color:#6b7280;margin-bottom:4px">Direction</label>
        <select name="type" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
            <option value="">All</option>
            <option value="credit" @selected(request('type') === 'credit')>Credit (+)</option>
            <option value="debit" @selected(request('type') === 'debit')>Debit (−)</option>
        </select>
    </div>
    <div>
        <label style="display:block;font-size:12px;color:#6b7280;margin-bottom:4px">From</label>
        <input type="date" name="from" value="{{ request('from') }}"
               style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
    </div>
    <div>
        <label style="display:block;font-size:12px;color:#6b7280;margin-bottom:4px">To</label>
        <input type="date" name="to" value="{{ request('to') }}"
               style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:13px">
    </div>
    <button type="submit" style="padding:7px 16px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer">
        Filter
    </button>
    @if(request()->hasAny(['event','type','from','to']))
    <a href="{{ route('admin.clients.transactions', $token) }}"
       style="padding:7px 16px;background:#e5e7eb;color:#374151;border-radius:6px;font-size:13px;text-decoration:none">
        Clear
    </a>
    @endif
</form>

{{-- ── Ledger table ─────────────────────────────────────────────────────── --}}
<div class="card">
<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Event</th>
            <th>Direction</th>
            <th style="text-align:right">Amount</th>
            <th style="text-align:right">Balance Before</th>
            <th style="text-align:right">Balance After</th>
            <th>Reference ID</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transactions as $tx)
        @php $isPlatformFee = $tx->event === 'platform_fee'; @endphp
        <tr style="{{ $isPlatformFee ? 'background:#fffbeb;' : '' }}">
            <td style="font-size:12px;color:#6b7280;white-space:nowrap">
                {{ $tx->created_at->format('Y-m-d H:i:s') }}
            </td>

            {{-- ── Event badge — extra styling for platform_fee ── --}}
            <td>
                @if($isPlatformFee)
                    <span style="background:#fde68a;color:#92400e;padding:3px 9px;border-radius:4px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px">
                        💰 Platform fee
                    </span>
                @else
                    <span style="background:#f3f4f6;padding:2px 8px;border-radius:4px;font-size:12px;color:#374151">
                        {{ str_replace('_', ' ', ucfirst($tx->event)) }}
                    </span>
                @endif
            </td>

            <td>
                @if($tx->type === 'credit')
                    <span style="color:#059669;font-weight:600">+ Credit</span>
                @else
                    <span style="color:#dc2626;font-weight:600">− Debit</span>
                @endif
            </td>

            {{-- ── Amount — amber for platform_fee ── --}}
            <td style="text-align:right;font-weight:700;
                color:{{ $isPlatformFee ? '#b45309' : ($tx->type === 'credit' ? '#059669' : '#dc2626') }}">
                {{ $tx->type === 'credit' ? '+' : '−' }}${{ number_format((float)$tx->amount, 4) }}
                @if($isPlatformFee)
                    <div style="font-size:10px;font-weight:500;color:#d97706;margin-top:1px">margin</div>
                @endif
            </td>

            <td style="text-align:right;color:#6b7280">${{ number_format((float)$tx->balance_before, 4) }}</td>
            <td style="text-align:right;font-weight:600">${{ number_format((float)$tx->balance_after, 4) }}</td>

            <td style="font-size:11px;color:#6b7280;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                title="{{ $tx->reference_id }}">
                {{ $tx->reference_id ?? '—' }}
            </td>

            <td>
                @php
                    $statusStyles = [
                        'confirmed' => 'background:#d1fae5;color:#065f46',
                        'pending'   => 'background:#fef3c7;color:#92400e',
                        'reversed'  => 'background:#f3f4f6;color:#6b7280',
                    ];
                @endphp
                <span style="{{ $statusStyles[$tx->status] ?? 'color:#374151' }};font-size:11px;font-weight:600;padding:2px 8px;border-radius:999px;display:inline-block">
                    {{ ucfirst($tx->status) }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;color:#9ca3af;padding:32px">
                No transactions found.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:16px">
    {{ $transactions->links() }}
</div>
@endsection
