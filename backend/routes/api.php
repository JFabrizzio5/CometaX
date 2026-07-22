<?php

use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\StatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Público — el frontend lo consulta para mostrar el banner de mantenimiento
    // general y deshabilitar la sección de pagos mientras Cashier/Stripe (Fase 2)
    // no esté integrado.
    Route::get('/status', [StatusController::class, 'index']);

    // Contacto de la landing: máx 2 envíos por IP por hora.
    Route::post('/contacto', [ContactController::class, 'store'])->middleware('throttle:2,60');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', function (Request $request) {
            return $request->user();
        });
    });
});
