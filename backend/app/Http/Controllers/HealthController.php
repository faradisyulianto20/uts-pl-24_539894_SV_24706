<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'db' => ['status' => 'ok'],
            'cache' => ['status' => 'ok'],
        ];
        $healthy = true;

        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (\Throwable $e) {
            $checks['db'] = ['status' => 'down'];
            $healthy = false;
        }

        try {
            $key = 'health_check_'.bin2hex(random_bytes(8));
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            if ($value !== 'ok') {
                $checks['cache'] = ['status' => 'down'];
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'down'];
            $healthy = false;
        }

        $status = $healthy ? 'healthy' : 'unhealthy';
        $code = $healthy ? 200 : 503;

        Log::log($healthy ? 'info' : 'warning', 'health.check', [
            'actor_user_id' => null,
            'actor_role' => null,
            'action' => 'health_check',
            'reason' => $healthy ? 'all_checks_ok' : 'component_unhealthy',
            'status' => $status,
            'checks' => array_map(fn ($c) => $c['status'], $checks),
        ]);

        return response()->json([
            'status' => $status,
            'checks' => $checks,
        ], $code);
    }
}
