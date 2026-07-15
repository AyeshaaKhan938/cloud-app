@props(['url'])
@php
    $logoSrc = null;

    $logoCandidates = [
        resource_path('images/vmfs-logo.jpg'),
        resource_path('images/vmfs-logo.jpeg'),
        resource_path('images/vmfs-logo.png'),
        public_path('images/vmfs-logo.jpg'),
        public_path('images/vmfs-logo.jpeg'),
        public_path('images/vmfs-logo.png'),
    ];

    foreach ($logoCandidates as $logoPath) {
        if (! file_exists($logoPath)) {
            continue;
        }

        if (isset($message)) {
            $logoSrc = $message->embed($logoPath);
        } else {
            $mime = str_ends_with(strtolower($logoPath), '.png') ? 'image/png' : 'image/jpeg';
            $logoSrc = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        break;
    }
@endphp
<tr>
<td class="header">
@if ($logoSrc)
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ $logoSrc }}" class="logo" alt="VMFS USA" width="75" height="75">
</a>
@endif
</td>
</tr>
