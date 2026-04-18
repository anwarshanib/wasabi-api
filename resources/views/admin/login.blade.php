<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Wasabi Card</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .box {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo h1 { font-size: 22px; font-weight: 800; color: #111827; }
        .logo h1 span { color: #10b981; }
        .logo p { font-size: 13px; color: #6b7280; margin-top: 4px; }

        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        input {
            width: 100%; padding: 10px 14px; border: 1px solid #d1d5db;
            border-radius: 8px; font-size: 14px; outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.12); }
        .error-msg { font-size: 12px; color: #ef4444; margin-top: 4px; }

        .alert-error {
            background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b;
            border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px;
        }

        .btn {
            width: 100%; padding: 10px; border: none; border-radius: 8px;
            background: #10b981; color: #fff; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: background .15s;
        }
        .btn:hover { background: #059669; }
    </style>
</head>
<body>

<div class="box">
    <div class="logo">
        <h1>Wasabi <span>Admin</span></h1>
        <p>API Token Manager</p>
    </div>

    @if($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@example.com" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn">Sign In</button>
    </form>
</div>

</body>
</html>
