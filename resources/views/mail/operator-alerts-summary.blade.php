<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMFS operator alerts</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#52525b;">
@php
    $logoPath = collect([
        resource_path('images/vmfs-logo.jpg'),
        resource_path('images/vmfs-logo.png'),
        public_path('images/vmfs-logo.jpg'),
        public_path('images/vmfs-logo.png'),
    ])->first(fn (string $path): bool => file_exists($path));

    $logoSrc = null;
    if ($logoPath !== null) {
        // Gmail blocks inline base64 — use CID embed when sending, HTTPS URL as fallback.
        if (isset($message)) {
            $logoSrc = $message->embed($logoPath);
        } else {
            $logoSrc = rtrim((string) config('app.url'), '/').'/images/'.basename($logoPath);
        }
    }

    $adminUrl = rtrim((string) config('app.url'), '/').'/admin';
@endphp
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f4f4f5;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="570" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:8px;overflow:hidden;">
                <tr>
                    <td align="center" style="padding:24px 24px 12px;">
                        @if ($logoSrc)
                            <a href="{{ $adminUrl }}" style="text-decoration:none;">
                                <img src="{{ $logoSrc }}" alt="VMFS USA" width="75" height="75" style="display:block;border:0;">
                            </a>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 32px 24px;">
                        <h1 style="margin:0 0 16px;font-size:18px;color:#18181b;">VMFS operator alerts</h1>
                        <p style="margin:0 0 20px;font-size:16px;line-height:1.5;">The following alerts are active on your machines:</p>

                        @foreach ($alerts as $alert)
                            <hr style="border:none;border-top:1px solid #e4e4e7;margin:20px 0;">
                            <p style="margin:0 0 8px;font-size:16px;line-height:1.5;">
                                <strong>{{ $alert['title'] }}</strong> ({{ $alert['severity'] }})
                            </p>
                            <p style="margin:0 0 8px;font-size:16px;line-height:1.5;">
                                <strong>Machine:</strong> {{ $alert['machine_name'] }} — {{ $alert['machine_number'] }}
                            </p>
                            <p style="margin:0;font-size:16px;line-height:1.5;">{{ $alert['message'] }}</p>
                        @endforeach

                        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:28px auto 0;">
                            <tr>
                                <td align="center" style="border-radius:6px;background-color:#18181b;">
                                    <a href="{{ $adminUrl }}" style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;">
                                        Open admin dashboard
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:24px 0 0;font-size:16px;line-height:1.5;">Thanks,</p>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:16px 32px;background-color:#f4f4f5;font-size:12px;color:#71717a;">
                        © {{ date('Y') }} VMFS USA. All rights reserved.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
