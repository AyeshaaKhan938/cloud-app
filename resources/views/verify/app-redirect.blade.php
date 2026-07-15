<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMFS USA — Age Verification</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .card {
            max-width: 420px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
        }
        h1 { font-size: 1.25rem; margin: 0 0 0.75rem; }
        p { color: #94a3b8; line-height: 1.5; margin: 0 0 1rem; }
        a.btn {
            display: inline-block;
            background: #38bdf8;
            color: #082f49;
            font-weight: 600;
            text-decoration: none;
            padding: 0.85rem 1.25rem;
            border-radius: 10px;
            margin: 0.25rem;
        }
        .stores { margin-top: 1rem; font-size: 0.875rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Open VMFS USA app</h1>
        @if ($sessionId !== '')
            <p>Age verification continues in the VMFS USA mobile app. If it did not open automatically, tap the button below.</p>
            <a class="btn" id="open-app" href="{{ $appDeepLink }}">Open in app</a>
        @else
            <p>Scan the QR code on the VMFS kiosk to start age verification in the mobile app.</p>
        @endif
        <p class="stores">Don't have the app? Download <strong>VMFS USA</strong> from the App Store or Google Play.</p>
    </div>
    @if ($sessionId !== '')
    <script>
        window.location.replace(@json($appDeepLink));
    </script>
    @endif
</body>
</html>
