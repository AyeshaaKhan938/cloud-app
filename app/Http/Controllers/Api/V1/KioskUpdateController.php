<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class KioskUpdateController extends Controller
{
    public function check(Request $request, KioskVersionController $kioskVersionController): JsonResponse
    {
        return $kioskVersionController->show($request);
    }
}
