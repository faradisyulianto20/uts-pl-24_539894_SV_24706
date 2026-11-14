<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/p10-log-demo', function () {
    Log::info('p10.stdout.demo', [
        'actor' => 'system',
        'action' => 'verify_stdout_logging',
        'reason' => 'p10_soal1_proof',
    ]);

    return response()->json(['ok' => true, 'message' => 'log emitted to stdout']);
});

Route::get('/metrics', function () {
    $requestsTotal = 1;
    try {
        if (\Illuminate\Support\Facades\Cache::has('app_http_requests_total')) {
            $requestsTotal = \Illuminate\Support\Facades\Cache::increment('app_http_requests_total');
        } else {
            \Illuminate\Support\Facades\Cache::forever('app_http_requests_total', 1);
        }
    } catch (\Throwable $e) {
        $requestsTotal = 1;
    }

    $postsTotal = 0;
    try {
        $postsTotal = \App\Models\Post::count();
    } catch (\Throwable $e) {
        $postsTotal = 0;
    }

    $output = "# HELP app_up Status ketersediaan backend Laravel (1 = UP, 0 = DOWN)\n";
    $output .= "# TYPE app_up gauge\n";
    $output .= "app_up 1\n\n";

    $output .= "# HELP app_http_requests_total Total akumulasi permintaan HTTP yang dilayani oleh backend\n";
    $output .= "# TYPE app_http_requests_total counter\n";
    $output .= "app_http_requests_total {$requestsTotal}\n\n";

    $output .= "# HELP app_posts_total Jumlah total artikel/postingan di database\n";
    $output .= "# TYPE app_posts_total gauge\n";
    $output .= "app_posts_total {$postsTotal}\n";

    return response($output, 200, [
        'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8'
    ]);
});
