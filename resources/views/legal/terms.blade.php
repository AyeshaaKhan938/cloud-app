<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service — VMFS USA</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 42rem; margin: 2rem auto; padding: 0 1.25rem; color: #1b1b18; line-height: 1.6; }
        h1 { font-size: 1.75rem; }
        h2 { font-size: 1.15rem; margin-top: 1.5rem; }
        p, li { color: #333; }
        a { color: #d97706; }
        .updated { color: #666; font-size: 0.9rem; }
    </style>
</head>
<body>
    <h1>Terms of Service</h1>
    <p class="updated">Last updated: {{ now()->toFormattedDateString() }}</p>
    <p>By using the VMFS USA mobile app, you agree to these terms.</p>

    <h2>1. Service description</h2>
    <p>VMFS USA provides a companion mobile app for VMFS vending machines, including age verification for regulated products and promotional lottery/coupon code lookup.</p>

    <h2>2. Eligibility</h2>
    <p>You must be at least {{ $minAge }} years old to use age verification features. The app is not a gambling application — lottery code lookup displays promotional prizes tied to physical vending machine products only.</p>

    <h2>3. Age verification</h2>
    <p>You agree to submit accurate government-issued identification when prompted at a VMFS kiosk. Misrepresentation of age or identity may result in denied service and account restrictions.</p>

    <h2>4. Acceptable use</h2>
    <ul>
        <li>Use the app only for legitimate VMFS vending machine interactions</li>
        <li>Do not attempt to circumvent age verification or code validation</li>
        <li>Do not upload fraudulent or altered identification documents</li>
    </ul>

    <h2>5. Disclaimer</h2>
    <p>The app is provided "as is". VMFS USA is not liable for vending machine availability, product dispensing failures, or third-party verification delays beyond our reasonable control.</p>

    <h2>6. Contact</h2>
    <p>Questions: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
    <p><a href="{{ url('/privacy') }}">Privacy Policy</a></p>
</body>
</html>
