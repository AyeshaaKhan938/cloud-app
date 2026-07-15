<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aligns filesystem "public" disk URLs with the current HTTP host/port in local.
 *
 * Filament builds image src from config('filesystems.disks.public.url'), which defaults to
 * APP_URL + '/storage'. If APP_URL is http://localhost:8000 but you serve on another port
 * or host (e.g. 127.0.0.1:8080, or Valet .test), the browser requests the wrong origin and
 * you get net::ERR_CONNECTION_REFUSED.
 */
final class SyncPublicDiskUrlForLocalRequests
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocal()) {
            $root = $request->getSchemeAndHttpHost();

            config([
                'filesystems.disks.public.url' => rtrim($root, '/').'/storage',
            ]);
        }

        return $next($request);
    }
}
