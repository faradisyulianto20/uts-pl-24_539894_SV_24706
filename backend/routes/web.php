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
