<?php

return [
    'name' => env('APP_NAME', 'AAMEVI'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'America/Santiago',
    'locale' => 'es',
    'fallback_locale' => 'es',
    'faker_locale' => 'es_ES',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'aliases' => [
        'Log' => Illuminate\Support\Facades\Log::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'DB' => Illuminate\Support\Facades\DB::class,
    ],
];
