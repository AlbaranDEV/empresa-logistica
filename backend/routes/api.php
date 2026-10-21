<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

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
