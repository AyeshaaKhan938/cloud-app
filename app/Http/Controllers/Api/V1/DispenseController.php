<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\ProductLotteryCode;
use App\Services\Kiosk\OperatorAlertEmailService;
use App\Services\Machines\MachineLotteryStockGuard;
use App\Support\VendingSlotLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Registra en el backend que un producto fue despachado físicamente por la máquina.
 *
 * La Flutter app llama este endpoint DESPUÉS de que el Control Board confirma
 * el despacho vía serial, para que quede el registro en el sistema.
 *
 * Además de actualizar el ProductLotteryCode, crea (o actualiza) una Order
 * para trazabilidad financiera y reportes del dashboard.
 */
final class DispenseController extends Controller
{
    /**
     * POST /api/v1/dispense
     *
     * Body:
     *   - lottery_code      string  El código de lotería que se redimió
     *   - machine_no        string  Número de serie de la máquina
     *   - line_number       int     Slot físico que se despachó
     *   - status            string  'success' | 'failed'
     *   - error             string  Mensaje de error opcional si status=failed
     *   - payment_method    string  'cash' | 'card' | 'other'  (opcional, default: 'cash')
     *   - payment_reference string  Referencia del terminal de pago (opcional)
     */
    public function store(Request $request, OperatorAlertEmailService $alertEmailService): JsonResponse
    {
        $data = $request->validate([
            'lottery_code' => ['required', 'string', 'max:32'],
            'machine_no' => ['required', 'string', 'max:64'],
            'line_number' => ['required', 'integer', 'min:1', 'max:'.VendingSlotLayout::MAX_SLOT_NUMBER],
            'status' => ['required', 'string', 'in:success,failed'],
            'error' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'in:cash,card,other', 'max:32'],
            'payment_reference' => ['nullable', 'string', 'max:128'],
        ]);

        if (VendingSlotLayout::isRedLineSlot((int) $data['line_number'])) {
            throw ValidationException::withMessages([
                'line_number' => MachineLotteryStockGuard::RED_LINE_SLOT_MESSAGE,
            ]);
        }

        // ── Buscar el código de lotería ───────────────────────────────────────
        // Aceptamos códigos tanto redimidos como no redimidos:
        // - Si el código aún no estaba redimido, lo marcamos ahora (flujo cupón físico).
        // - Si ya estaba redimido (flujo draw), solo actualizamos el despacho.
        $code = ProductLotteryCode::query()
            ->with(['prize', 'productLottery'])
            ->where('code', $data['lottery_code'])
            ->first();

        if ($code === null) {
            throw ValidationException::withMessages([
                'lottery_code' => 'Code not found.',
            ]);
        }

        $success = $data['status'] === 'success';

        // ── Marcar como redimido si aún no lo estaba ──────────────────────────
        if ($code->redeemed_at === null && $success) {
            $code->redeemed_at = now();
        }

        // ── Actualizar el código con el resultado del despacho ────────────────
        $code->fill([
            'dispense_status' => $data['status'],
            'dispense_machine_no' => $data['machine_no'],
            'dispense_line' => $data['line_number'],
            'dispense_error' => $data['error'] ?? null,
            'dispensed_at' => $success ? now() : null,
        ])->save();

        // ── Buscar el slot para trazabilidad ──────────────────────────────────
        $slot = MachineSlot::query()
            ->with('product')
            ->whereHas('machine', fn ($q) => $q->where('machine_number', $data['machine_no']))
            ->where('line_number', $data['line_number'])
            ->first();

        // ── Crear la orden de venta ───────────────────────────────────────────
        $order = Order::create([
            'machine_no' => $data['machine_no'],
            'product_lottery_code_id' => $code->id,
            'machine_slot_id' => $slot?->id,
            'product_name' => $slot?->product?->name ?? $code->productLottery?->name,
            'line_number' => $data['line_number'],
            'prize_name' => $code->prize?->name,
            'prize_amount' => (float) ($code->prize?->prize_amount ?? 0) > 0
                ? $code->prize->prize_amount
                : ($slot?->price ?? 0),
            'payment_method' => $data['payment_method'] ?? 'cash',
            'payment_reference' => $data['payment_reference'] ?? null,
            'status' => $success ? 'completed' : 'failed',
            'notes' => $data['error'] ?? null,
            'completed_at' => $success ? now() : null,
        ]);

        if (! $success) {
            $alertEmailService->sendInstantDispenseFailureAlert($order);
        }

        // ── Decrementar stock si el despacho fue exitoso ──────────────────────
        if ($success && $slot !== null) {
            if ($slot->current_stock <= 0) {
                throw ValidationException::withMessages([
                    'line_number' => 'This slot is out of stock.',
                ]);
            }

            $slot->decrement('current_stock');
        }

        return response()->json([
            'ok' => true,
            'message' => $success
                ? 'Dispense recorded successfully.'
                : 'Dispense failure recorded.',
        ]);
    }
}
