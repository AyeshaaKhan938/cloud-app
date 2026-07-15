<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Minimal bridge page: kiosk QR → opens VMFS mobile app via deep link.
 * Replaces the old static web/verify/index.html companion page.
 */
final class VerifyAppRedirectController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sessionId = $request->query('session', '');

        return view('verify.app-redirect', [
            'sessionId' => is_string($sessionId) ? $sessionId : '',
            'appDeepLink' => $sessionId !== ''
                ? 'vmfsusa://verify?session='.$sessionId
                : 'vmfsusa://verify',
        ]);
    }
}
