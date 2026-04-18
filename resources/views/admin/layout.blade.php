<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Wasabi Card</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            background: #f3f4f6;
            color: #111827;
            min-height: 100vh;
        }

        /* ── Nav ── */
        .nav {
            background: #111827;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            height: 56px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .nav-brand { font-size: 16px; font-weight: 700; letter-spacing: .4px; color: #fff; text-decoration: none; }
        .nav-brand span { color: #10b981; }
        .nav-right { display: flex; align-items: center; gap: 16px; }
        .nav-right a { color: #9ca3af; text-decoration: none; font-size: 13px; }
        .nav-right a:hover { color: #fff; }

        /* ── Layout ── */
        .container { max-width: 1100px; margin: 32px auto; padding: 0 20px; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .page-title { font-size: 20px; font-weight: 700; }

        /* ── Buttons ── */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; font-weight: 500; text-decoration: none; transition: background .15s; }
        .btn-primary { background: #10b981; color: #fff; }
        .btn-primary:hover { background: #059669; }
        .btn-danger  { background: #ef4444; color: #fff; }
        .btn-danger:hover  { background: #dc2626; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }
        .btn-warning { background: #f59e0b; color: #fff; }
        .btn-warning:hover { background: #d97706; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }

        /* ── Cards ── */
        .card { background: #fff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08); overflow: hidden; }
        .card-body { padding: 24px; }

        /* ── Alerts ── */
        .alert { border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; line-height: 1.5; }
        .alert-success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }
        .alert-danger  { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
        .alert-token   { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
        .alert code    { font-family: 'SFMono-Regular', Consolas, monospace; background: rgba(0,0,0,.07); padding: 2px 6px; border-radius: 4px; font-size: 12px; word-break: break-all; }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .6px; color: #6b7280; padding: 10px 14px; border-bottom: 2px solid #e5e7eb; white-space: nowrap; }
        td { padding: 12px 14px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f9fafb; }

        /* ── Badge ── */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red   { background: #fee2e2; color: #991b1b; }

        /* ── Form ── */
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password], textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;
            font-size: 14px; outline: none; transition: border-color .15s;
        }
        input:focus, textarea:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.12); }
        .form-hint { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .error-msg { font-size: 12px; color: #ef4444; margin-top: 4px; }

        /* ── Pagination ── */
        .pagination { display: flex; gap: 6px; justify-content: flex-end; margin-top: 20px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 5px 12px; border-radius: 6px; font-size: 13px; text-decoration: none; color: #374151; background: #fff; border: 1px solid #e5e7eb; }
        .pagination a:hover { background: #f3f4f6; }
        .pagination .active { background: #10b981; color: #fff; border-color: #10b981; }
        .pagination .disabled { opacity: .4; pointer-events: none; }

        .text-muted { color: #9ca3af; font-size: 12px; }
        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 0; }
    </style>
</head>
<body>

<nav class="nav">
    <a href="{{ route('admin.tokens.index') }}" class="nav-brand">Wasabi <span>Admin</span></a>
    <div class="nav-right">
        <span style="color:#9ca3af">API Token Manager</span>
        <form method="POST" action="{{ route('admin.logout') }}" style="margin:0">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:13px;padding:0">Logout</button>
        </form>
    </div>
</nav>

<div class="container">
    @yield('content')
</div>

</body>
</html>
