<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMFS USA daily machine report</title>
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
        if (isset($message)) {
            $logoSrc = $message->embed($logoPath);
        } else {
            $logoSrc = rtrim((string) config('app.url'), '/').'/images/'.basename($logoPath);
        }
    }

    $adminUrl = rtrim((string) config('app.url'), '/').'/admin/reports/business-analytics';
    $portfolio = $report['portfolio'] ?? [];
    $perUnit = $report['per_unit'] ?? [];
    $period = $report['period'] ?? [];
@endphp
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f4f4f5;padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="620" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:8px;overflow:hidden;">
                <tr>
                    <td align="center" style="padding:24px 24px 12px;">
                        @if ($logoSrc)
                            <a href="{{ $adminUrl }}" style="text-decoration:none;">
                                <img src="{{ $logoSrc }}" alt="VMFS USA" width="75" height="75" style="display:block;border:0;">
                            </a>
                        @else
                            <p style="margin:0;font-size:20px;font-weight:700;color:#18181b;">VMFS USA</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 32px 24px;">
                        <h1 style="margin:0 0 8px;font-size:20px;color:#18181b;">Good morning, {{ $owner->name }}</h1>
                        <p style="margin:0 0 20px;font-size:16px;line-height:1.5;">
                            Here is your daily machine performance summary for
                            <strong>{{ $period['from'] ?? '' }}@if(($period['from'] ?? '') !== ($period['to'] ?? '')) – {{ $period['to'] ?? '' }}@endif</strong>.
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 24px;background:#fafafa;border:1px solid #e4e4e7;border-radius:8px;">
                            <tr>
                                <td style="padding:16px;">
                                    <p style="margin:0 0 8px;font-size:14px;color:#71717a;text-transform:uppercase;letter-spacing:0.04em;">Portfolio totals</p>
                                    <p style="margin:0 0 6px;font-size:16px;line-height:1.5;">
                                        <strong>Items sold:</strong> {{ number_format((int) ($portfolio['sales']['orders'] ?? 0)) }}
                                    </p>
                                    <p style="margin:0 0 6px;font-size:16px;line-height:1.5;">
                                        <strong>Sales revenue:</strong> ${{ number_format((float) ($portfolio['sales']['revenue'] ?? 0), 2) }}
                                    </p>
                                    <p style="margin:0;font-size:16px;line-height:1.5;">
                                        <strong>Gross profit:</strong> ${{ number_format((float) ($portfolio['pnl']['gross_profit'] ?? 0), 2) }}
                                        <span style="color:#71717a;">({{ $portfolio['pnl']['gross_margin_label'] ?? 'N/A' }})</span>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <h2 style="margin:0 0 12px;font-size:17px;color:#18181b;">Per machine breakdown</h2>

                        @forelse ($perUnit as $unit)
                            @php
                                $machine = $unit['machine'] ?? [];
                                $metrics = $unit['metrics'] ?? [];
                            @endphp
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 16px;border:1px solid #e4e4e7;border-radius:8px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <p style="margin:0 0 8px;font-size:16px;font-weight:600;color:#18181b;">
                                            {{ $machine['name'] ?? 'Machine' }}
                                            <span style="font-weight:400;color:#71717a;">({{ $machine['number'] ?? '—' }})</span>
                                        </p>
                                        <p style="margin:0 0 4px;font-size:15px;line-height:1.5;">
                                            Items sold: <strong>{{ number_format((int) ($metrics['sales']['orders'] ?? 0)) }}</strong>
                                        </p>
                                        <p style="margin:0 0 4px;font-size:15px;line-height:1.5;">
                                            Sales: <strong>${{ number_format((float) ($metrics['sales']['revenue'] ?? 0), 2) }}</strong>
                                        </p>
                                        <p style="margin:0;font-size:15px;line-height:1.5;">
                                            Profit: <strong>${{ number_format((float) ($metrics['pnl']['gross_profit'] ?? 0), 2) }}</strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        @empty
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">No machine activity was recorded for this period.</p>
                        @endforelse

                        <table cellpadding="0" cellspacing="0" role="presentation" style="margin:12px auto 0;">
                            <tr>
                                <td align="center" style="border-radius:6px;background-color:#d97706;">
                                    <a href="{{ $adminUrl }}" style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;">
                                        View full analytics
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:24px 0 0;font-size:16px;line-height:1.5;">Thanks,<br>VMFS USA</p>
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
