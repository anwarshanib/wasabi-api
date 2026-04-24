@extends('admin.layout')

@section('title', 'Fee Settings')

@section('content')

<div class="page-header">
    <h1 class="page-title">Fee Settings</h1>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

{{-- ─────────────────────────────────────────────────────────── --}}
{{-- Section 1: Fee Rates                                         --}}
{{-- ─────────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-body">
        <h2 style="font-size:15px;font-weight:700;margin-bottom:18px">Fee Rates</h2>

        <form method="POST" action="{{ route('admin.fees.settings') }}">
            @csrf
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Fee Type</th>
                            <th>Rate (%)</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['key' => 'deposit',          'label' => 'Deposit (Top-up)',   'unit' => '%',  'hint' => 'Percentage of received amount'],
                            ['key' => 'card_application', 'label' => 'Card Application',   'unit' => '$',  'hint' => 'Fixed dollar amount per card'],
                            ['key' => 'fx',               'label' => 'FX (Non-USD)',        'unit' => '%',  'hint' => 'Percentage of authorized amount'],
                        ] as $row)
                        @php $s = $feeSettings[$row['key']] ?? null; @endphp
                        <tr>
                            <td>
                                <strong>{{ $row['label'] }}</strong>
                                <div style="font-size:11px;color:#6b7280;margin-top:2px">{{ $row['hint'] }}</div>
                            </td>
                            <td style="width:180px">
                                <div style="display:flex;align-items:center;gap:6px">
                                <span style="font-weight:600;color:#374151">{{ $row['unit'] }}</span>
                                <input
                                    type="number"
                                    name="{{ $row['key'] }}_rate"
                                    value="{{ old($row['key'].'_rate', $s?->rate ?? 0) }}"
                                    min="0" max="100" step="0.01"
                                    style="width:100px"
                                >
                                </div>
                            </td>
                            <td style="width:100px">
                                <input
                                    type="checkbox"
                                    name="{{ $row['key'] }}_active"
                                    value="1"
                                    {{ ($s?->is_active) ? 'checked' : '' }}
                                    style="width:16px;height:16px;cursor:pointer"
                                >
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary">Save Rates</button>
            </div>
        </form>
    </div>
</div>

{{-- ─────────────────────────────────────────────────────────── --}}
{{-- Section 2: Fee Wallets                                       --}}
{{-- ─────────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-body">
        <h2 style="font-size:15px;font-weight:700;margin-bottom:4px">Fee Wallets</h2>
        <p class="text-muted" style="margin-bottom:18px">
            Select which Wasabi account to deduct fees <strong>from</strong> (Source — your WALLET account)
            and which account to collect fees <strong>into</strong> (Destination — fee MARGIN account).
        </p>

        @if(empty($wasabiAccounts))
        <p class="text-muted">No Wasabi accounts found.</p>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Account ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Available Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($wasabiAccounts as $account)
                    <tr>
                        <td class="text-muted">{{ $account['accountId'] }}</td>
                        <td>
                            <strong>{{ $account['accountName'] }}</strong>
                            @if($account['accountId'] === $currentSource)
                                <span class="badge badge-green" style="margin-left:6px">Source</span>
                            @endif
                            @if($account['accountId'] === $currentDestination)
                                <span class="badge" style="background:#dbeafe;color:#1e40af;margin-left:6px">Destination</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $account['accountType'] }}</td>
                        <td>{{ number_format($account['availableBalance'], 2) }} {{ $account['currency'] }}</td>
                        <td>
                            <div class="actions">
                                <form method="POST" action="{{ route('admin.fees.wallet.source') }}" style="margin:0">
                                    @csrf
                                    <input type="hidden" name="account_id" value="{{ $account['accountId'] }}">
                                    <button type="submit" class="btn btn-secondary btn-sm"
                                        {{ $account['accountId'] === $currentSource ? 'disabled' : '' }}>
                                        Set as Source
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.fees.wallet.destination') }}" style="margin:0">
                                    @csrf
                                    <input type="hidden" name="account_id" value="{{ $account['accountId'] }}">
                                    <button type="submit" class="btn btn-primary btn-sm"
                                        {{ $account['accountId'] === $currentDestination ? 'disabled' : '' }}>
                                        Set as Destination
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <hr class="divider" style="margin:24px 0">

        <h3 style="font-size:13px;font-weight:600;margin-bottom:12px">Create New Fee Wallet</h3>
        <form method="POST" action="{{ route('admin.fees.wallet.create') }}" style="display:flex;gap:10px;align-items:flex-end">
            @csrf
            <div class="form-group" style="margin:0;flex:1;max-width:320px">
                <label for="wallet_name">Wallet Name</label>
                <input type="text" id="wallet_name" name="name" placeholder="e.g. Fee Collection Wallet" maxlength="80">
            </div>
            <button type="submit" class="btn btn-secondary">Create Wallet</button>
        </form>
    </div>
</div>

{{-- ─────────────────────────────────────────────────────────── --}}
{{-- Section 3: Fee Ledger Report                                  --}}
{{-- ─────────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-body">
        <h2 style="font-size:15px;font-weight:700;margin-bottom:18px">Fee Ledger</h2>

        @if($ledgerRows->isEmpty())
        <p class="text-muted">No fee transactions yet.</p>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Fee Type</th>
                        <th>Base Amount</th>
                        <th>Fee Amount</th>
                        <th>Reference ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ledgerRows as $row)
                    <tr>
                        <td class="text-muted" style="white-space:nowrap">{{ $row->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $row->apiToken?->name ?? '—' }}</td>
                        <td>
                            <span style="text-transform:capitalize">{{ str_replace('_', ' ', $row->fee_type) }}</span>
                        </td>
                        <td>{{ number_format($row->base_amount, 4) }} {{ $row->currency }}</td>
                        <td><strong>{{ number_format($row->fee_amount, 4) }} {{ $row->currency }}</strong></td>
                        <td class="text-muted" style="font-size:11px">{{ $row->reference_id ?? '—' }}</td>
                        <td>
                            @if($row->status === 'transferred')
                                <span class="badge badge-green">Transferred</span>
                            @elseif($row->status === 'failed')
                                <span class="badge badge-red">Failed</span>
                            @else
                                <span class="badge" style="background:#fef3c7;color:#92400e">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $ledgerRows->links() }}
        @endif
    </div>
</div>

@endsection
