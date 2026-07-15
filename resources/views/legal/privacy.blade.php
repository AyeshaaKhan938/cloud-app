<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — VMFS USA</title>
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
    <h1>Privacy Policy</h1>
    <p class="updated">Last updated: {{ now()->toFormattedDateString() }}</p>
    <p>VMFS USA ("we", "us") operates the VMFS USA mobile app and vending machine platform. This policy explains how we collect, use, and protect your information.</p>

    <h2>1. Who this app is for</h2>
    <p>The VMFS USA app is intended for adults aged {{ $minAge }} or older. It is not directed at children under 13. We do not knowingly collect data from children.</p>

    <h2>2. Information we collect</h2>
    <ul>
        <li><strong>Age verification:</strong> When you scan a QR code at a VMFS kiosk, you may upload a photo of a government-issued ID (driver's license, ID card, or passport) and document type. This is used solely to confirm you meet the minimum age for regulated vending products.</li>
        <li><strong>Lottery / coupon lookup:</strong> You may enter a promotional code to view prize information. We process the code you submit and return prize details from our servers.</li>
        <li><strong>Technical data:</strong> Standard server logs (IP address, request time) for security and rate limiting. We do not use the app for advertising tracking.</li>
    </ul>

    <h2>3. How we use your information</h2>
    <ul>
        <li>Verify age for age-restricted vending machine purchases</li>
        <li>Link your verification session to the kiosk that created it</li>
        <li>Validate promotional lottery or coupon codes</li>
        <li>Prevent fraud and duplicate code redemption</li>
    </ul>

    <h2>4. ID document retention</h2>
    <p>Uploaded ID images are retained for up to <strong>{{ $documentRetentionHours }} hours</strong> for verification processing, then deleted from our systems. Verification status (pass/fail) may be kept longer for audit and compliance purposes without retaining the document image.</p>
    <p>Verification may be processed by our platform or a third-party identity provider (current provider: <strong>{{ $provider }}</strong>).</p>

    <h2>5. Data sharing</h2>
    <p>We do not sell your personal information. We may share data only with:</p>
    <ul>
        <li>Identity verification providers performing age checks on our behalf</li>
        <li>Service providers hosting our infrastructure under contract</li>
        <li>Authorities when required by law</li>
    </ul>

    <h2>6. Your rights</h2>
    <p>Depending on your location, you may request access, correction, or deletion of your personal data. Contact us at <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>

    <h2>7. Security</h2>
    <p>Data is transmitted over HTTPS. ID uploads are stored temporarily on secured servers with access restricted to authorized systems.</p>

    <h2>8. Changes</h2>
    <p>We may update this policy. Continued use of the app after changes constitutes acceptance.</p>

    <h2>9. Contact</h2>
    <p>VMFS USA — <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></p>
    <p><a href="{{ url('/terms') }}">Terms of Service</a></p>
</body>
</html>
