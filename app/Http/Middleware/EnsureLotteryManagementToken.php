<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureLotteryManagementToken
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('services.lottery.management_token');

        if (! is_string($configured) || $configured === '') {
            return response()->json([
                'message' => 'Lottery management API is not configured.',
            ], 503);
        }

        $bearer = $request->bearerToken();

        if ($bearer === null || ! hash_equals($configured, $bearer)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
