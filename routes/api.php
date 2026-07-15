<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AdminMachineController;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminSlotController;
use App\Http\Controllers\Api\V1\AdvertisementController;
use App\Http\Controllers\Api\V1\AgeVerificationSessionController;
use App\Http\Controllers\Api\V1\DispenseController;
use App\Http\Controllers\Api\V1\KioskUpdateController;
use App\Http\Controllers\Api\V1\KioskVersionController;
use App\Http\Controllers\Api\V1\MachineSlotController;
use App\Http\Controllers\Api\V1\ProductLotteryCodeController;
use App\Http\Controllers\Api\V1\ProductLotteryManagementController;
use App\Http\Controllers\Api\V1\ScratchCardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('lottery-codes/lookup', [ProductLotteryCodeController::class, 'lookup'])
        ->middleware('throttle:60,1');

    Route::post('product-lottery-draw/{token}', [ProductLotteryCodeController::class, 'draw'])
        ->where('token', '[a-z0-9]{20,64}')
        ->middleware('throttle:60,1');

    // Ten Point Media scratch-card flow — validate code + record dispense outcome
    Route::post('scratch-card/redeem', [ScratchCardController::class, 'redeem'])
        ->middleware('throttle:30,1');
    Route::post('scratch-card/confirm', [ScratchCardController::class, 'confirm'])
        ->middleware('throttle:30,1');

    // Remote APK updates — kiosk polls this on launch + hourly
    Route::get('kiosk/update-check', [KioskUpdateController::class, 'check'])
        ->middleware('throttle:60,1');

    // Registra el resultado del despacho físico desde la Flutter app
    Route::post('dispense', [DispenseController::class, 'store'])
        ->middleware('throttle:60,1');

    // Inventario de slots + anuncios por slot — kiosk Flutter al arrancar
    Route::get('machines/{machineNo}/slots', [MachineSlotController::class, 'index'])
        ->middleware('throttle:120,1');

    Route::get('machines/{machineNo}/advertisements', [AdvertisementController::class, 'index'])
        ->middleware('throttle:60,1');

    Route::get('kiosk/version', [KioskVersionController::class, 'show'])
        ->middleware('throttle:60,1');

    Route::post('age-verification/sessions', [AgeVerificationSessionController::class, 'store'])
        ->middleware('throttle:60,1');

    Route::get('age-verification/sessions/{session}', [AgeVerificationSessionController::class, 'show'])
        ->whereUuid('session')
        ->middleware('throttle:120,1');

    Route::post('age-verification/sessions/{session}/document', [AgeVerificationSessionController::class, 'uploadDocument'])
        ->whereUuid('session')
        ->middleware('throttle:30,1');

    Route::middleware(['lottery.management', 'throttle:120,1'])->group(function (): void {
        Route::get('product-lotteries', [ProductLotteryManagementController::class, 'index']);
        Route::get('product-lotteries/{product_lottery}', [ProductLotteryManagementController::class, 'show']);

        // ── Admin panel (kiosk) ──────────────────────────────────────────────
        Route::prefix('admin')->group(function (): void {
            // Machine dashboard + inventory
            Route::get('machines/{machineNo}/dashboard', [AdminMachineController::class, 'dashboard']);
            Route::get('machines/{machineNo}/slots', [AdminMachineController::class, 'slots']);

            // Slot management
            Route::patch('slots/{id}', [AdminSlotController::class, 'update']);

            // Orders / sales history
            Route::get('machines/{machineNo}/orders', [AdminOrderController::class, 'index']);

            // Product catalog — list, create, update
            Route::get('products', [AdminProductController::class, 'index']);
            Route::post('products', [AdminProductController::class, 'store']);
            Route::patch('products/{id}', [AdminProductController::class, 'update']);
        });
    });
});
