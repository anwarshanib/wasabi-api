<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wasabi Card API — Integration Guide</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green: #10b981;
            --green-dark: #059669;
            --green-light: #d1fae5;
            --bg: #f8fafc;
            --sidebar-bg: #0f172a;
            --sidebar-width: 260px;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --code-bg: #0f172a;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Inter', sans-serif;
            font-size: 15px;
            line-height: 1.7;
            color: var(--text);
            background: var(--bg);
            display: flex;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-brand h1 { font-size: 17px; font-weight: 800; color: #fff; }
        .sidebar-brand h1 span { color: var(--green); }
        .sidebar-brand p { font-size: 11px; color: #64748b; margin-top: 2px; text-transform: uppercase; letter-spacing: .6px; }

        .sidebar nav { padding: 16px 0; }
        .nav-section { padding: 8px 20px 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #475569; }
        .sidebar nav a {
            display: block; padding: 7px 20px;
            color: #94a3b8; text-decoration: none; font-size: 13px;
            transition: color .15s, background .15s;
            border-left: 3px solid transparent;
        }
        .sidebar nav a:hover { color: #fff; background: rgba(255,255,255,.04); }
        .sidebar nav a.active { color: var(--green); border-left-color: var(--green); background: rgba(16,185,129,.06); }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,.08);
            margin-top: auto;
        }
        .sidebar-footer a {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; color: #64748b; text-decoration: none;
            padding: 6px 12px; border-radius: 6px; background: rgba(255,255,255,.05);
        }
        .sidebar-footer a:hover { color: #fff; background: rgba(255,255,255,.1); }

        /* ── Main ── */
        .main {
            margin-left: var(--sidebar-width);
            flex: 1;
            max-width: 860px;
            padding: 48px 56px;
        }

        /* ── Sections ── */
        .section { margin-bottom: 64px; scroll-margin-top: 32px; }
        h2 { font-size: 22px; font-weight: 800; color: var(--text); margin-bottom: 12px; padding-bottom: 10px; border-bottom: 2px solid var(--border); }
        h3 { font-size: 15px; font-weight: 700; color: var(--text); margin: 24px 0 8px; }
        p { margin-bottom: 12px; color: var(--text); }

        /* ── Hero ── */
        .hero { background: var(--sidebar-bg); color: #fff; border-radius: 14px; padding: 36px 40px; margin-bottom: 48px; }
        .hero h1 { font-size: 28px; font-weight: 800; margin-bottom: 10px; }
        .hero h1 span { color: var(--green); }
        .hero p { color: #94a3b8; font-size: 15px; max-width: 560px; line-height: 1.6; margin-bottom: 20px; }
        .hero-links { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; transition: .15s; }
        .btn-green { background: var(--green); color: #fff; }
        .btn-green:hover { background: var(--green-dark); }
        .btn-outline { background: rgba(255,255,255,.08); color: #fff; border: 1px solid rgba(255,255,255,.15); }
        .btn-outline:hover { background: rgba(255,255,255,.14); }

        /* ── Info box ── */
        .info-box { border-radius: 8px; padding: 14px 18px; margin: 16px 0; font-size: 13px; line-height: 1.6; display: flex; gap: 10px; }
        .info-box-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
        .info-green { background: var(--green-light); border: 1px solid #6ee7b7; color: #065f46; }
        .info-yellow { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; }
        .info-blue   { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }

        /* ── Code ── */
        pre {
            background: var(--code-bg); color: #e2e8f0;
            border-radius: 10px; padding: 20px 22px;
            overflow-x: auto; font-size: 13px; line-height: 1.65;
            margin: 12px 0;
            font-family: 'SFMono-Regular', 'Fira Code', Consolas, monospace;
        }
        pre .comment { color: #475569; }
        pre .key     { color: #93c5fd; }
        pre .string  { color: #a3e635; }
        pre .number  { color: #fb923c; }
        pre .method  { color: var(--green); font-weight: 700; }
        pre .url     { color: #f0abfc; }
        code {
            font-family: 'SFMono-Regular', Consolas, monospace;
            background: #f1f5f9; color: #be185d;
            padding: 2px 7px; border-radius: 5px; font-size: 12.5px;
        }
        pre code { background: none; color: inherit; padding: 0; font-size: inherit; }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); padding: 10px 14px; border-bottom: 2px solid var(--border); white-space: nowrap; background: #fff; }
        td { padding: 10px 14px; border-bottom: 1px solid var(--border); vertical-align: top; background: #fff; }
        tr:last-child td { border-bottom: none; }

        /* ── Badge ── */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; font-family: monospace; }
        .badge-post    { background: #dbeafe; color: #1e40af; }
        .badge-get     { background: #d1fae5; color: #065f46; }
        .badge-green   { background: var(--green-light); color: #065f46; }
        .badge-yellow  { background: #fffbeb; color: #92400e; }
        .badge-red     { background: #fee2e2; color: #991b1b; }

        /* ── Endpoint list ── */
        .endpoint-group { margin-bottom: 28px; }
        .endpoint-group-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--muted); margin-bottom: 8px; }
        .endpoint {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; border: 1px solid var(--border);
            border-radius: 8px; margin-bottom: 6px; background: #fff;
            font-size: 13px;
        }
        .endpoint code { background: none; color: var(--muted); padding: 0; font-size: 13px; }
        .endpoint-desc { margin-left: auto; color: var(--muted); font-size: 12px; }

        /* ── Webhook flow ── */
        .flow {
            display: flex; align-items: stretch; gap: 0;
            margin: 16px 0; font-size: 12px; flex-wrap: wrap;
        }
        .flow-step {
            flex: 1; min-width: 140px;
            background: #fff; border: 1px solid var(--border);
            padding: 14px 16px; text-align: center;
            position: relative;
        }
        .flow-step:first-child { border-radius: 8px 0 0 8px; }
        .flow-step:last-child  { border-radius: 0 8px 8px 0; }
        .flow-step + .flow-step { border-left: none; }
        .flow-step .step-num { font-size: 10px; font-weight: 700; color: var(--green); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .flow-step .step-title { font-weight: 700; font-size: 13px; margin-bottom: 4px; }
        .flow-step .step-sub { color: var(--muted); font-size: 11px; line-height: 1.4; }
        .flow-arrow { display: flex; align-items: center; color: var(--green); font-size: 18px; padding: 0 2px; }

        hr { border: none; border-top: 1px solid var(--border); margin: 32px 0; }

        @media(max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 24px 20px; }
        }
    </style>
</head>
<body>

<!-- ── Sidebar ── -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>Wasabi <span>Card</span></h1>
        <p>Developer Documentation</p>
    </div>
    <nav>
        <div class="nav-section">Getting Started</div>
        <a href="#overview">Overview</a>
        <a href="#authentication">Authentication</a>
        <a href="#request-format">Request Format</a>
        <a href="#response-format">Response Format</a>
        <a href="#errors">Error Codes</a>
        <a href="#rate-limiting">Rate Limiting</a>

        <div class="nav-section">API Reference</div>
        <a href="#common">Common</a>
        <a href="#wallet">Wallet</a>
        <a href="#cards">Cards</a>
        <a href="#cardholders">Cardholders</a>
        <a href="#accounts">Accounts</a>
        <a href="#work-orders">Work Orders</a>

        <div class="nav-section">Webhooks</div>
        <a href="#webhooks-overview">Overview</a>
        <a href="#webhooks-verify">Signature Verification</a>
        <a href="#webhook-events">Event Reference</a>
        <a href="#poll-events">Polling Events</a>

        <div class="nav-section">Interactive</div>
        <a href="/api/documentation" target="_blank">Swagger UI ↗</a>
    </nav>
    <div class="sidebar-footer">
        <a href="/api/documentation" target="_blank">🔗 Try in Swagger UI</a>
    </div>
</aside>

<!-- ── Main content ── -->
<main class="main">

    <!-- Hero -->
    <div class="hero">
        <h1>Wasabi <span>Card</span> API</h1>
        <p>A complete REST API integration layer for card issuance, cardholder management, wallet operations, and real-time webhook events.</p>
        <div class="hero-links">
            <a href="/api/documentation" target="_blank" class="btn btn-green">Open Interactive Docs ↗</a>
            <a href="#authentication" class="btn btn-outline">Quick Start →</a>
        </div>
    </div>

    <!-- Overview -->
    <div class="section" id="overview">
        <h2>Overview</h2>
        <p>The Wasabi Card API is a RESTful backend that wraps the Wasabi Card Open API platform. As a third-party developer you interact only with <strong>this API</strong> — you never call Wasabi directly.</p>

        <div class="info-box info-blue">
            <span class="info-box-icon">ℹ️</span>
            <div>All API endpoints are versioned under <code>/api/v1/</code>. All requests must use <strong>POST</strong> (or GET where noted). All payloads are JSON.</div>
        </div>

        <h3>Base URL</h3>
        <pre><span class="comment"># Production</span>
https://api.yourdomain.com

<span class="comment"># Sandbox / local development</span>
http://127.0.0.1:8000</pre>
    </div>

    <!-- Authentication -->
    <div class="section" id="authentication">
        <h2>Authentication</h2>
        <p>Every request to <code>/api/v1/*</code> must include your API key in the <code>X-API-KEY</code> request header.</p>

        <pre><span class="comment"># Example — all requests require this header</span>
<span class="key">X-API-KEY</span>: <span class="string">wc_YourSecretKeyHere</span>
<span class="key">Content-Type</span>: <span class="string">application/json</span></pre>

        <div class="info-box info-yellow">
            <span class="info-box-icon">⚠️</span>
            <div><strong>Keep your key secret.</strong> Treat it like a password. Do not commit it to source control or expose it in client-side code. If compromised, contact the administrator to revoke and reissue it.</div>
        </div>

        <h3>Getting a Key</h3>
        <p>API keys are issued by the platform administrator via the admin portal. Each key is tied to your developer account. Contact the admin to receive yours.</p>

        <h3>Missing or Invalid Key</h3>
        <pre>{
  <span class="key">"success"</span>: <span class="number">false</span>,
  <span class="key">"code"</span>:    <span class="number">401</span>,
  <span class="key">"msg"</span>:     <span class="string">"Invalid or missing API key."</span>,
  <span class="key">"data"</span>:    <span class="number">null</span>
}</pre>
    </div>

    <!-- Request Format -->
    <div class="section" id="request-format">
        <h2>Request Format</h2>
        <p>All requests use <strong>JSON bodies</strong> with <code>Content-Type: application/json</code>. Unless otherwise stated, endpoints use the <code>POST</code> method.</p>

        <h3>cURL Example</h3>
        <pre><span class="method">curl</span> -X POST <span class="url">https://api.yourdomain.com/api/v1/cardholders/occupations</span> \
  -H <span class="string">"X-API-KEY: wc_YourSecretKeyHere"</span> \
  -H <span class="string">"Content-Type: application/json"</span></pre>

        <h3>Request with Body</h3>
        <pre><span class="method">curl</span> -X POST <span class="url">https://api.yourdomain.com/api/v1/cards/balance</span> \
  -H <span class="string">"X-API-KEY: wc_YourSecretKeyHere"</span> \
  -H <span class="string">"Content-Type: application/json"</span> \
  -d <span class="string">'{
    "cardNo": "4111111111111111"
  }'</span></pre>
    </div>

    <!-- Response Format -->
    <div class="section" id="response-format">
        <h2>Response Format</h2>
        <p>Every response — success or error — uses the same JSON envelope:</p>

        <pre>{
  <span class="key">"success"</span>: <span class="number">true</span>,      <span class="comment">// boolean — true only when code=200</span>
  <span class="key">"code"</span>:    <span class="number">200</span>,       <span class="comment">// integer status code</span>
  <span class="key">"msg"</span>:     <span class="string">"Success"</span>,  <span class="comment">// human-readable message</span>
  <span class="key">"data"</span>:    { ... }    <span class="comment">// payload — object, array, or null</span>
}</pre>

        <p>Always check <code>success === true</code> (or <code>code === 200</code>) before processing <code>data</code>.</p>

        <h3>Successful List Response Example</h3>
        <pre>{
  <span class="key">"success"</span>: <span class="number">true</span>,
  <span class="key">"code"</span>:    <span class="number">200</span>,
  <span class="key">"msg"</span>:     <span class="string">"Success"</span>,
  <span class="key">"data"</span>: {
    <span class="key">"total"</span>:   <span class="number">42</span>,
    <span class="key">"records"</span>: [ ... ]
  }
}</pre>
    </div>

    <!-- Errors -->
    <div class="section" id="errors">
        <h2>Error Codes</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>HTTP Status</th><th>code</th><th>Meaning</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <tr><td><span class="badge badge-green">200</span></td><td>200</td><td>Success</td><td>Process <code>data</code></td></tr>
                    <tr><td><span class="badge badge-yellow">400</span></td><td>400</td><td>Bad request / business rule error</td><td>Check <code>msg</code> for details</td></tr>
                    <tr><td><span class="badge badge-red">401</span></td><td>401</td><td>Missing or invalid API key</td><td>Check your <code>X-API-KEY</code> header</td></tr>
                    <tr><td><span class="badge badge-yellow">422</span></td><td>422</td><td>Validation failed</td><td><code>data</code> contains field-level errors</td></tr>
                    <tr><td><span class="badge badge-yellow">429</span></td><td>429</td><td>Rate limit exceeded</td><td>Wait and retry. See <a href="#rate-limiting">Rate Limiting</a></td></tr>
                    <tr><td><span class="badge badge-red">502</span></td><td>502</td><td>Upstream Wasabi API error</td><td>Retry. If persistent, contact support</td></tr>

                </tbody>
            </table>
        </div>

        <h3>Validation Error Example</h3>
        <pre>{
  <span class="key">"success"</span>: <span class="number">false</span>,
  <span class="key">"code"</span>:    <span class="number">422</span>,
  <span class="key">"msg"</span>:     <span class="string">"Validation failed."</span>,
  <span class="key">"data"</span>: {
    <span class="key">"email"</span>: [ <span class="string">"The email field must be a valid email address."</span> ],
    <span class="key">"cardHolderModel"</span>: [ <span class="string">"The selected card holder model is invalid."</span> ]
  }
}</pre>
    </div>

    <!-- Rate Limiting -->
    <div class="section" id="rate-limiting">
        <h2>Rate Limiting</h2>
        <p>Each API key is limited to <strong>60 requests per minute</strong>. Exceeding this returns HTTP 429. Implement exponential back-off in your client.</p>

        <pre><span class="comment"># Retry strategy (example)</span>
attempt 1: immediate
attempt 2: wait 1s
attempt 3: wait 2s
attempt 4: wait 4s</pre>
    </div>

    <hr>

    <!-- API Reference -->
    <div class="section" id="common">
        <h2>Common — Reference Data</h2>
        <p>Lookup tables required for other API calls. Cache these responses — they rarely change.</p>

        <div class="endpoint-group">
            <div class="endpoint"><span class="badge badge-get">GET</span> <code>/api/v1/common/regions</code> <span class="endpoint-desc">Supported countries/regions</span></div>
            <div class="endpoint"><span class="badge badge-get">GET</span> <code>/api/v1/common/cities</code> <span class="endpoint-desc">City list with codes</span></div>
            <div class="endpoint"><span class="badge badge-get">GET</span> <code>/api/v1/common/cities/hierarchical</code> <span class="endpoint-desc">Cities grouped by region</span></div>
            <div class="endpoint"><span class="badge badge-get">GET</span> <code>/api/v1/common/mobile-codes</code> <span class="endpoint-desc">Supported phone area codes</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/common/files/upload</code> <span class="endpoint-desc">Upload KYC documents (multipart/form-data)</span></div>
        </div>
        <div class="info-box info-blue"><span class="info-box-icon">ℹ️</span><div>File upload returns a <code>fileId</code>. Pass it as <code>idFrontId</code>, <code>idBackId</code>, or <code>idHoldId</code> when creating a B2C cardholder.</div></div>
    </div>

    <div class="section" id="wallet">
        <h2>Wallet</h2>
        <p>On-chain deposit orders and wallet transaction history.</p>
        <div class="endpoint-group">
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/wallet/deposit</code> <span class="endpoint-desc">Create a deposit order (deprecated)</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/wallet/deposit/transactions</code> <span class="endpoint-desc">Deposit transaction history (deprecated)</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/wallet/v2/coins</code> <span class="endpoint-desc">Supported coins list</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/wallet/v2/create</code> <span class="endpoint-desc">Create wallet deposit address (V2)</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/wallet/v2/address-list</code> <span class="endpoint-desc">Wallet address list</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/wallet/v2/transactions</code> <span class="endpoint-desc">Transaction history V2</span></div>
        </div>
    </div>

    <div class="section" id="cards">
        <h2>Cards</h2>
        <p>Full card lifecycle: create, manage, fund, and query transaction history.</p>
        <div class="endpoint-group">
            <div class="endpoint-group-title">Card Setup</div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/support-bins</code> <span class="endpoint-desc">Supported card types and BINs</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/create-v2</code> <span class="endpoint-desc">Create a new virtual card (V2)</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/activate-physical</code> <span class="endpoint-desc">Activate a physical card</span></div>
        </div>
        <div class="endpoint-group">
            <div class="endpoint-group-title">Card Information</div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/info</code> <span class="endpoint-desc">Card details</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/sensitive</code> <span class="endpoint-desc">Encrypted card number, expiry, CVV</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/balance</code> <span class="endpoint-desc">Card balance</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/list</code> <span class="endpoint-desc">List all cards (paginated)</span></div>
        </div>
        <div class="endpoint-group">
            <div class="endpoint-group-title">Card Management</div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/update</code> <span class="endpoint-desc">Update card label / settings</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/freeze</code> <span class="endpoint-desc">Freeze card</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/unfreeze</code> <span class="endpoint-desc">Unfreeze card</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/cancel</code> <span class="endpoint-desc">Cancel (close) card</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/update-pin</code> <span class="endpoint-desc">Update card PIN</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/note</code> <span class="endpoint-desc">Update card note</span></div>
        </div>
        <div class="endpoint-group">
            <div class="endpoint-group-title">Card Funding</div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/deposit</code> <span class="endpoint-desc">Deposit funds to card</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/withdraw</code> <span class="endpoint-desc">Withdraw funds from card</span></div>
        </div>
        <div class="endpoint-group">
            <div class="endpoint-group-title">Transaction History</div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/purchase-transactions</code> <span class="endpoint-desc">Purchase/debit transactions</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/operation-transactions-v2</code> <span class="endpoint-desc">Operation transactions V2</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/auth-transactions</code> <span class="endpoint-desc">Authorization transactions</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/auth-fee-transactions</code> <span class="endpoint-desc">Authorization fee transactions</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/3ds-transactions</code> <span class="endpoint-desc">3DS OTP / auth URL transactions</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cards/simulate-auth</code> <span class="endpoint-desc">Simulate authorization (sandbox only)</span></div>
        </div>
    </div>

    <div class="section" id="cardholders">
        <h2>Cardholders</h2>
        <p>KYC onboarding and cardholder lifecycle. Cardholder creation is async — the initial response returns <code>wait_audit</code> and the final result arrives via webhook.</p>
        <div class="endpoint-group">
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cardholders/occupations</code> <span class="endpoint-desc">Supported occupation codes</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cardholders/create-v2</code> <span class="endpoint-desc">Create cardholder — B2B or B2C model</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cardholders/update-v2</code> <span class="endpoint-desc">Update cardholder (when status=reject)</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cardholders/list</code> <span class="endpoint-desc">List cardholders (paginated, filterable)</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/cardholders/update-email</code> <span class="endpoint-desc">Update cardholder email (when status=pass_audit)</span></div>
        </div>

        <h3>B2B vs B2C Model</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Field</th><th>B2B</th><th>B2C</th></tr></thead>
                <tbody>
                    <tr><td>cardHolderModel</td><td><code>"B2B"</code></td><td><code>"B2C"</code></td></tr>
                    <tr><td>Common fields (name, address, mobile…)</td><td>✅ Required</td><td>✅ Required</td></tr>
                    <tr><td>gender, nationality, occupation</td><td>—</td><td>✅ Required</td></tr>
                    <tr><td>idType, idNumber, idFrontId, idBackId, idHoldId</td><td>—</td><td>✅ Required</td></tr>
                    <tr><td>kycVerification (provider, referenceId)</td><td>—</td><td>Optional (required for type 111065)</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section" id="accounts">
        <h2>Accounts</h2>
        <p>Merchant wallet accounts — balances, ledger history, and fund transfers between accounts.</p>
        <div class="endpoint-group">
            <div class="endpoint"><span class="badge badge-get">GET</span>  <code>/api/v1/accounts/assets</code> <span class="endpoint-desc">Total assets overview</span></div>
            <div class="endpoint"><span class="badge badge-get">GET</span>  <code>/api/v1/accounts/</code> <span class="endpoint-desc">Account list</span></div>
            <div class="endpoint"><span class="badge badge-get">GET</span>  <code>/api/v1/accounts/single</code> <span class="endpoint-desc">Single account details</span></div>
            <div class="endpoint"><span class="badge badge-get">GET</span>  <code>/api/v1/accounts/transactions</code> <span class="endpoint-desc">Ledger transaction history</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/accounts/create</code> <span class="endpoint-desc">Create shared account</span></div>
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/accounts/transfer</code> <span class="endpoint-desc">Transfer funds between accounts</span></div>
        </div>
    </div>

    <div class="section" id="work-orders">
        <h2>Work Orders</h2>
        <p>Submit and track platform work orders (e.g. card activation requests).</p>
        <div class="endpoint-group">
            <div class="endpoint"><span class="badge badge-post">POST</span> <code>/api/v1/work-orders/</code> <span class="endpoint-desc">Submit a work order</span></div>
            <div class="endpoint"><span class="badge badge-get">GET</span>  <code>/api/v1/work-orders/</code> <span class="endpoint-desc">List work orders</span></div>
        </div>
    </div>

    <hr>

    <!-- Webhooks -->
    <div class="section" id="webhooks-overview">
        <h2>Webhooks — Overview</h2>
        <p>Several operations are <strong>asynchronous</strong>: the API returns an immediate pending status, and the final result is delivered later via webhook. Your server must poll the <a href="#poll-events">Webhook Events API</a> to retrieve these results.</p>

        <div class="flow">
            <div class="flow-step">
                <div class="step-num">Step 1</div>
                <div class="step-title">You call the API</div>
                <div class="step-sub">e.g. create cardholder</div>
            </div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">
                <div class="step-num">Step 2</div>
                <div class="step-title">Immediate response</div>
                <div class="step-sub">status: wait_audit</div>
            </div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">
                <div class="step-num">Step 3</div>
                <div class="step-title">Platform reviews</div>
                <div class="step-sub">minutes to hours</div>
            </div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">
                <div class="step-num">Step 4</div>
                <div class="step-title">Webhook stored</div>
                <div class="step-sub">final status: pass_audit or reject</div>
            </div>
            <div class="flow-arrow">→</div>
            <div class="flow-step">
                <div class="step-num">Step 5</div>
                <div class="step-title">You poll events</div>
                <div class="step-sub">GET /api/v1/webhook-events</div>
            </div>
        </div>

        <h3>Async Operations (always poll for final result)</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>API Call</th><th>Initial Response</th><th>Final via Event category</th></tr></thead>
                <tbody>
                    <tr><td><code>POST /cardholders/create-v2</code></td><td><code>wait_audit</code></td><td><code>card_holder</code></td></tr>
                    <tr><td><code>POST /cardholders/update-v2</code></td><td><code>wait_audit</code></td><td><code>card_holder</code></td></tr>
                    <tr><td><code>POST /cardholders/update-email</code></td><td><code>wait_audit</code></td><td><code>card_holder_change_email</code></td></tr>
                    <tr><td><code>POST /cards/activate-physical</code></td><td>pending</td><td><code>physical_card</code></td></tr>
                    <tr><td><code>POST /cards/deposit</code></td><td>pending</td><td><code>card_transaction</code></td></tr>
                    <tr><td><code>POST /cards/withdraw</code></td><td>pending</td><td><code>card_transaction</code></td></tr>
                    <tr><td><code>POST /cards/cancel</code></td><td>pending</td><td><code>card_transaction</code></td></tr>
                    <tr><td>Card purchase (external)</td><td>—</td><td><code>card_auth_transaction</code></td></tr>
                    <tr><td>3DS triggered (external)</td><td>—</td><td><code>card_3ds</code></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section" id="webhook-events">
        <h2>Webhook Event Reference</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>category</th><th>Trigger</th><th>reference_id field</th></tr></thead>
                <tbody>
                    <tr><td><code>card_holder</code></td><td>Cardholder status change</td><td><code>holderId</code></td></tr>
                    <tr><td><code>card_holder_change_email</code></td><td>Email update status</td><td><code>holderId</code></td></tr>
                    <tr><td><code>card_transaction</code></td><td>Deposit / withdraw / cancel final result</td><td><code>orderNo</code></td></tr>
                    <tr><td><code>card_auth_transaction</code></td><td>Authorization — may push multiple times as status flows</td><td><code>tradeNo</code></td></tr>
                    <tr><td><code>card_fee_patch</code></td><td>Authorization fee applied</td><td><code>tradeNo</code></td></tr>
                    <tr><td><code>card_3ds</code></td><td>3DS OTP code / auth URL / activation code</td><td><code>tradeNo</code></td></tr>
                    <tr><td><code>physical_card</code></td><td>Physical card activation result</td><td><code>cardNo</code></td></tr>
                    <tr><td><code>work</code></td><td>Work order status change</td><td><code>orderNo</code></td></tr>
                    <tr><td><code>wallet_transaction</code></td><td>Wallet deposit/withdrawal</td><td><code>orderNo</code></td></tr>
                    <tr><td><code>wallet_transaction_v2</code></td><td>Wallet transaction history V2</td><td><code>orderNo</code></td></tr>
                </tbody>
            </table>
        </div>

        <h3>Cardholder Status Values</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>status</th><th>Meaning</th></tr></thead>
                <tbody>
                    <tr><td><code>wait_audit</code></td><td>Pending review — not final, keep polling</td></tr>
                    <tr><td><code>under_review</code></td><td>In review — not final, keep polling</td></tr>
                    <tr><td><code>pass_audit</code></td><td>✅ Approved — final</td></tr>
                    <tr><td><code>reject</code></td><td>❌ Rejected — final. Check <code>description</code> for reason</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section" id="poll-events">
        <h2>Polling Webhook Events</h2>
        <p>Use <code>GET /api/v1/webhook-events</code> to retrieve stored async results. Filter by <code>category</code> and <code>reference_id</code> for the specific operation you're waiting on.</p>

        <h3>Example: Poll for cardholder creation result</h3>
        <pre><span class="comment"># Step 1 — create cardholder, get holderId from response</span>
<span class="method">curl</span> -X POST <span class="url">https://api.yourdomain.com/api/v1/cardholders/create-v2</span> \
  -H <span class="string">"X-API-KEY: wc_..."</span> -d <span class="string">'{ "cardHolderModel": "B2C", ... }'</span>
<span class="comment"># Response: { "data": { "holderId": 124024, "status": "wait_audit" } }</span>

<span class="comment"># Step 2 — poll until status is pass_audit or reject</span>
<span class="method">curl</span> <span class="url">"https://api.yourdomain.com/api/v1/webhook-events?category=card_holder&amp;reference_id=124024"</span> \
  -H <span class="string">"X-API-KEY: wc_..."</span></pre>

        <h3>Query Parameters</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>category</code></td><td>string</td><td>Filter by event type (e.g. <code>card_holder</code>)</td></tr>
                    <tr><td><code>reference_id</code></td><td>string</td><td>holderId, tradeNo, orderNo, or cardNo depending on category</td></tr>
                    <tr><td><code>merchant_order_no</code></td><td>string</td><td>Filter by your own order number</td></tr>
                    <tr><td><code>status</code></td><td>string</td><td>Filter by status value (e.g. <code>pass_audit</code>)</td></tr>
                    <tr><td><code>from</code></td><td>datetime</td><td>Filter from date. Format: <code>Y-m-d H:i:s</code></td></tr>
                    <tr><td><code>to</code></td><td>datetime</td><td>Filter to date. Format: <code>Y-m-d H:i:s</code></td></tr>
                    <tr><td><code>per_page</code></td><td>integer</td><td>Records per page. Max 100, default 15</td></tr>
                    <tr><td><code>page</code></td><td>integer</td><td>Page number</td></tr>
                </tbody>
            </table>
        </div>

        <h3>Sample Response</h3>
        <pre>{
  <span class="key">"success"</span>: <span class="number">true</span>,
  <span class="key">"code"</span>: <span class="number">200</span>,
  <span class="key">"data"</span>: {
    <span class="key">"total"</span>: <span class="number">1</span>,
    <span class="key">"data"</span>: [
      {
        <span class="key">"id"</span>: <span class="number">5</span>,
        <span class="key">"category"</span>:          <span class="string">"card_holder"</span>,
        <span class="key">"reference_id"</span>:     <span class="string">"124024"</span>,
        <span class="key">"merchant_order_no"</span>: <span class="string">"ORDER20250101000001"</span>,
        <span class="key">"status"</span>:           <span class="string">"pass_audit"</span>,
        <span class="key">"payload"</span>: {          <span class="comment">// full raw payload from Wasabi</span>
          <span class="key">"holderId"</span>:    <span class="number">124024</span>,
          <span class="key">"status"</span>:      <span class="string">"pass_audit"</span>,
          <span class="key">"description"</span>: <span class="string">"Approved"</span>
        },
        <span class="key">"created_at"</span>: <span class="string">"2026-04-18T10:30:00.000000Z"</span>
      }
    ]
  }
}</pre>

        <div class="info-box info-yellow">
            <span class="info-box-icon">⚠️</span>
            <div>
                <strong>Polling interval recommendation:</strong> Poll every 30–60 seconds. Stop once you receive a final status (<code>pass_audit</code> or <code>reject</code>). <code>card_auth_transaction</code> events may be pushed multiple times for the same <code>tradeNo</code> as status progresses.
            </div>
        </div>
    </div>

    <div style="margin-top:64px; padding:24px; background:#fff; border-radius:10px; border:1px solid var(--border); text-align:center; color:var(--muted); font-size:13px;">
        For full interactive API reference with request/response schemas and a live testing console, visit
        <a href="/api/documentation" style="color:var(--green); font-weight:600">the Swagger UI →</a>
    </div>

</main>

</body>
</html>
