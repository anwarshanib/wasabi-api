<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wasabi Card API — API Reference</title>
    <style>
        body { margin: 0; padding: 0; }

        /* Top nav bar above Redoc */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 52px;
            padding: 0 24px;
            background: #0f172a;
            border-bottom: 1px solid rgba(255,255,255,.08);
            position: sticky; top: 0; z-index: 9999;
        }
        .top-bar-brand {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 15px; font-weight: 800; color: #fff; text-decoration: none;
        }
        .top-bar-brand span { color: #10b981; }
        .top-bar-links { display: flex; gap: 8px; }
        .top-bar-links a {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 12px; font-weight: 600; text-decoration: none;
            padding: 6px 14px; border-radius: 6px; transition: .15s;
        }
        .link-ghost { color: #94a3b8; }
        .link-ghost:hover { color: #fff; background: rgba(255,255,255,.08); }
        .link-primary { background: #10b981; color: #fff; }
        .link-primary:hover { background: #059669; }
    </style>
</head>
<body>

<nav class="top-bar">
    <a href="{{ url('/docs/guide') }}" class="top-bar-brand">Wasabi <span>Card</span> API</a>
    <div class="top-bar-links">
        <a href="{{ url('/docs/guide') }}" class="link-ghost">Integration Guide</a>
        <a href="{{ url('/api/documentation') }}" target="_blank" class="link-primary">Swagger UI ↗</a>
    </div>
</nav>

<div id="redoc-container"></div>

<script src="https://cdn.jsdelivr.net/npm/redoc@latest/bundles/redoc.standalone.js"></script>
<script>
    Redoc.init(
        '{{ url("/docs") }}',
        {
            expandResponses: '200',
            hideDownloadButton: false,
            pathInMiddlePanel: false,
            nativeScrollbars: false,
            theme: {
                colors: { primary: { main: '#10b981' }, success: { main: '#10b981' }, tonalOffset: 0.2 },
                typography: {
                    fontSize: '14px', lineHeight: '1.7',
                    fontFamily: '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif',
                    headings: { fontFamily: '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif', fontWeight: '800' },
                    code: { fontSize: '13px', fontFamily: 'SFMono-Regular, Fira Code, Consolas, monospace' }
                },
                sidebar: { width: '260px', backgroundColor: '#0f172a', textColor: '#94a3b8' },
                rightPanel: { backgroundColor: '#1e293b' }
            },
            // Override servers so example URLs use the correct base (live or sandbox).
            specTransformer: function(spec) {
                spec.servers = [{ url: '{{ rtrim(config("app.url"), "/") }}', description: 'Current environment' }];
                return spec;
            }
        },
        document.getElementById('redoc-container')
    );
</script>

</body>
</html>
