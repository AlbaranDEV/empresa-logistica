<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\VentasPorClienteController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Consultas públicas (solo lectura)
Route::apiResource('pedidos', PedidoController::class)->only(['index', 'show']);
Route::get('/clientes', [ClienteController::class, 'index']);
Route::get('/reportes/ventas-por-cliente', VentasPorClienteController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('pedidos', PedidoController::class)->except(['index', 'show']);
});

Route::get('/health', function () {
    $check = function (callable $probe): string {
        try {
            $probe();

            return 'ok';
        } catch (Throwable $e) {
            return 'error: '.$e->getMessage();
        }
    };

    $services = [
        'mysql' => $check(fn () => DB::connection()->getPdo()),
        'redis' => $check(fn () => Redis::connection()->ping()),
    ];

    $healthy = ! array_filter($services, fn ($status) => $status !== 'ok');

    return response()->json([
        'status' => $healthy ? 'ok' : 'degraded',
        'services' => $services,
    ], $healthy ? 200 : 503);
});
