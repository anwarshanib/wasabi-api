@extends('admin.layout')

@section('title', 'API Tokens')

@section('content')

<div class="page-header">
    <h1 class="page-title">API Tokens</h1>
    <a href="{{ route('admin.tokens.create') }}" class="btn btn-primary">+ New Token</a>
</div>

{{-- New token created — show raw value ONCE --}}
@if(session('new_token'))
<div class="alert alert-token">
    <strong>⚠ Save this token now — it will never be shown again.</strong><br><br>
    Token for <strong>{{ session('new_token_name') }}</strong>:<br><br>
    <code>{{ session('new_token') }}</code><br><br>
    Copy it and send it to the developer. This value cannot be recovered from the database.
</div>
@endif

{{-- Revealed token --}}
@if(session('revealed_token'))
<div class="alert alert-token">
    <strong>Revealed token for "{{ session('revealed_name') }}":</strong><br><br>
    <code>{{ session('revealed_token') }}</code>
</div>
@endif

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Last Used</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tokens as $token)
                <tr>
                    <td class="text-muted">{{ $token->id }}</td>
                    <td><strong>{{ $token->name }}</strong></td>
                    <td class="text-muted">{{ $token->email ?? '—' }}</td>
                    <td class="text-muted">{{ $token->description ?? '—' }}</td>
                    <td>
                        @if($token->is_active)
                            <span class="badge badge-green">Active</span>
                        @else
                            <span class="badge badge-red">Disabled</span>
                        @endif
                    </td>
                    <td class="text-muted">
                        {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
                    </td>
                    <td class="text-muted">{{ $token->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="actions">
                            {{-- Reveal raw token --}}
                            <form method="POST" action="{{ route('admin.tokens.reveal', $token) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" title="Reveal raw token">Reveal</button>
                            </form>

                            {{-- Enable / Disable --}}
                            <form method="POST" action="{{ route('admin.tokens.toggle', $token) }}">
                                @csrf
                                @if($token->is_active)
                                    <button type="submit" class="btn btn-warning btn-sm">Disable</button>
                                @else
                                    <button type="submit" class="btn btn-primary btn-sm">Enable</button>
                                @endif
                            </form>

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('admin.tokens.destroy', $token) }}"
                                  onsubmit="return confirm('Permanently delete token for {{ addslashes($token->name) }}? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:40px; color:#9ca3af;">
                        No tokens yet. <a href="{{ route('admin.tokens.create') }}" style="color:#10b981">Create the first one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($tokens->hasPages())
<div class="pagination">
    @if($tokens->onFirstPage())
        <span class="disabled">← Prev</span>
    @else
        <a href="{{ $tokens->previousPageUrl() }}">← Prev</a>
    @endif

    @foreach($tokens->getUrlRange(1, $tokens->lastPage()) as $page => $url)
        @if($page == $tokens->currentPage())
            <span class="active">{{ $page }}</span>
        @else
            <a href="{{ $url }}">{{ $page }}</a>
        @endif
    @endforeach

    @if($tokens->hasMorePages())
        <a href="{{ $tokens->nextPageUrl() }}">Next →</a>
    @else
        <span class="disabled">Next →</span>
    @endif
</div>
@endif

@endsection
