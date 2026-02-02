<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Simple health check for Docker/orchestration (avoids Laravel's /up view path issues)
Route::get('/health', fn () => response('ok', 200));
