<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LookupProductLotteryCodeRequest;
use App\Models\ProductLottery;
use App\Services\Products\ProductLotteryDrawService;
use App\Services\Products\ProductLotteryKioskLookupResponder;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProductLotteryCodeController extends Controller
{
    public function lookup(
        LookupProductLotteryCodeRequest $request,
        ProductLotteryKioskLookupResponder $responder,
    ): JsonResponse {
        return $responder->respond($request->validated('code'));
    }

    /**
     * Public draw: resolves {@see ProductLottery::$public_draw_token} (Filament). Response JSON is English.
     */
    public function draw(string $token, ProductLotteryDrawService $drawService): JsonResponse
    {
        $lottery = ProductLottery::query()
            ->where('public_draw_token', $token)
            ->first();

        if ($lottery === null) {
            throw new NotFoundHttpException('Lottery not found.');
        }

        $code = $drawService->claimNextAndRedeem($lottery);
        $prize = $code->prize;
        $tierLabel = filled($prize->name) ? $prize->name : $prize->tier_code;

        return response()->json([
            // Monto real del premio en dólares (ej. "4.99")
            'price' => number_format((float) $prize->prize_amount, 2),
            // Etiqueta del tier para el mensaje de felicitación
            'message' => 'You got '.$tierLabel.' — $'.number_format((float) $prize->prize_amount, 2).' off!',
            // Código asignado (para trazabilidad en el reporte de despacho).
            'code' => $code->code,
            // Slot físico de la máquina. La Flutter app lo usa para enviar
            // el comando de despacho al Control Board vía UART serial.
            // null si el prize aún no tiene slot configurado en el admin.
            'lineNumber' => $prize->line_number,
            'machineNo' => $lottery->machine_no ?? '',
        ]);
    }
}
