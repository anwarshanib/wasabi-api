@extends('admin.layout')

@section('title', 'Client Balances')

@section('content')
<div class="page-header">
    <h1 class="page-title">Client Balances</h1>
</div>

{{-- ── Summary stat cards ─────────────────────────────────────────────── --}}
@php
    $totalBalance   = $clients->sum(fn($r) => (float)$r['balance']);
    $totalDeposited = $clients->sum(fn($r) => (float)$r['total_deposited']);
    $totalFees      = $clients->sum(fn($r) => (float)$r['total_fees_paid']);
    $activeClients  = $clients->filter(fn($r) => $r['token']->is_active)->count();
@endphp
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
    <div style="background:#fff;border-radius:10px;padding:20px 22px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;margin-bottom:8px">Active Clients</div>
        <div style="font-size:28px;font-weight:700;color:#111827">{{ $activeClients }}</div>
        <div style="font-size:12px;color:#9ca3af;margin-top:4px">of {{ $clients->count() }} total</div>
    </div>
    <div style="background:#fff;border-radius:10px;padding:20px 22px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;margin-bottom:8px">Total Balances</div>
        <div style="font-size:28px;font-weight:700;color:#059669">${{ number_format($totalBalance, 2) }}</div>
        <div style="font-size:12px;color:#9ca3af;margin-top:4px">USD held across all clients</div>
    </div>
    <div style="background:#fff;border-radius:10px;padding:20px 22px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;margin-bottom:8px">Total Deposited</div>
        <div style="font-size:28px;font-weight:700;color:#111827">${{ number_format($totalDeposited, 2) }}</div>
        <div style="font-size:12px;color:#9ca3af;margin-top:4px">cumulative inflows</div>
    </div>
    <div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;padding:20px 22px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:rgba(255,255,255,.7);margin-bottom:8px">Platform Fees Earned</div>
        <div style="font-size:28px;font-weight:700;color:#fff">${{ number_format($totalFees, 2) }}</div>
        <div style="font-size:12px;color:rgba(255,255,255,.7);margin-top:4px">margin collected from clients</div>
    </div>
</div>

{{-- ── Client table ────────────────────────────────────────────────────── --}}
<div class="card" style="overflow:visible">
<div class="table-wrap">
<table class="table" style="min-width:960px">
    <thead>
        <tr>
            <th>Client</th>
            <th>Contact</th>
            <th>Member Since</th>
            <th style="text-align:right">Balance (USD)</th>
            <th style="text-align:right">Total Deposited</th>
            <th style="text-align:right">Total Spent</th>
            <th style="text-align:right;background:#fef3c7;color:#92400e">Platform Fees</th>
            <th>Last Deposit</th>
            <th style="position:sticky;right:0;background:#f9fafb;z-index:2"></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($clients as $row)
        <tr>
            {{-- ── Client identity ── --}}
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:50%;background:#e0f2fe;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#0369a1;flex-shrink:0">
                        {{ strtoupper(substr($row['token']->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:600;color:#111827">{{ $row['token']->name }}</div>
                        @if ($row['token']->description)
                            <div style="font-size:11px;color:#9ca3af;margin-top:1px">{{ $row['token']->description }}</div>
                        @endif
                        @if (!$row['token']->is_active)
                            <span style="display:inline-block;margin-top:2px;background:#fee2e2;color:#991b1b;font-size:10px;font-weight:600;padding:1px 6px;border-radius:999px">Inactive</span>
                        @else
                            <span style="display:inline-block;margin-top:2px;background:#d1fae5;color:#065f46;font-size:10px;font-weight:600;padding:1px 6px;border-radius:999px">Active</span>
                        @endif
                    </div>
                </div>
            </td>

            {{-- ── Contact ── --}}
            <td>
                <div style="font-size:13px;color:#374151">{{ $row['token']->email ?? '—' }}</div>
                <div style="font-size:11px;color:#9ca3af;margin-top:2px">ID #{{ $row['token']->id }}</div>
            </td>

            {{-- ── Member since ── --}}
            <td style="font-size:12px;color:#6b7280;white-space:nowrap">
                {{ $row['token']->created_at->format('d M Y') }}
            </td>

            {{-- ── Balance ── --}}
            <td style="text-align:right">
                <div style="font-size:16px;font-weight:700;color:{{ (float)$row['balance'] > 0 ? '#059669' : '#6b7280' }}">
                    ${{ number_format((float)$row['balance'], 4) }}
                </div>
                <div style="font-size:11px;color:#9ca3af">{{ $row['currency'] }}</div>
            </td>

            {{-- ── Total deposited ── --}}
            <td style="text-align:right;color:#374151">${{ number_format((float)$row['total_deposited'], 2) }}</td>

            {{-- ── Total spent ── --}}
            <td style="text-align:right;color:#374151">${{ number_format((float)$row['total_spent'], 2) }}</td>

            {{-- ── Platform fees — highlighted ── --}}
            <td style="text-align:right;background:#fffbeb">
                <div style="font-size:14px;font-weight:700;color:#b45309">${{ number_format((float)$row['total_fees_paid'], 2) }}</div>
                @if((float)$row['total_deposited'] > 0)
                    <div style="font-size:11px;color:#d97706;margin-top:2px">
                        {{ number_format((float)$row['total_fees_paid'] / (float)$row['total_deposited'] * 100, 1) }}% of deposits
                    </div>
                @endif
            </td>

            {{-- ── Last deposit ── --}}
            <td style="color:#6b7280;font-size:12px;white-space:nowrap">
                {{ $row['last_deposit_at'] ? $row['last_deposit_at']->diffForHumans() : '—' }}
            </td>

            {{-- ── Action ── --}}
            <td style="position:sticky;right:0;background:#fff;box-shadow:-2px 0 6px rgba(0,0,0,.06)">
                <a href="{{ route('admin.clients.transactions', $row['token']) }}" class="btn btn-secondary btn-sm">
                    Ledger →
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;color:#9ca3af;padding:40px">
                No clients yet.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>{{-- table-wrap --}}
</div>{{-- card --}}
@endsection
