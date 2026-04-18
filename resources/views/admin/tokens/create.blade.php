@extends('admin.layout')

@section('title', 'Create Token')

@section('content')

<div class="page-header">
    <h1 class="page-title">Create API Token</h1>
    <a href="{{ route('admin.tokens.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div class="card" style="max-width: 560px">
    <div class="card-body">
        <p style="margin-bottom:20px; color:#6b7280; font-size:13px; line-height:1.6">
            A secure token will be generated automatically. <strong>Copy it immediately</strong> after creation — it will only be shown once.
            The developer must pass it as the <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px">X-API-KEY</code> header on every request.
        </p>

        <form method="POST" action="{{ route('admin.tokens.store') }}">
            @csrf

            <div class="form-group">
                <label for="name">Name <span style="color:#ef4444">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       placeholder="e.g. Acme Corp — Production" maxlength="120" required>
                <div class="form-hint">A label to identify this client (company name, environment, etc.)</div>
                @error('name') <div class="error-msg">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="email">Contact Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       placeholder="developer@example.com" maxlength="120">
                <div class="form-hint">Optional — developer or company email for reference</div>
                @error('email') <div class="error-msg">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"
                          placeholder="e.g. Sandbox access only — card APIs" maxlength="255"
                          style="resize:vertical">{{ old('description') }}</textarea>
                <div class="form-hint">Optional notes for your records</div>
                @error('description') <div class="error-msg">{{ $message }}</div> @enderror
            </div>

            <hr class="divider" style="margin-bottom:20px">

            <button type="submit" class="btn btn-primary">Generate Token</button>
        </form>
    </div>
</div>

@endsection
