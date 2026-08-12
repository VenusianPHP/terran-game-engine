<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Frame pacing
    |--------------------------------------------------------------------------
    */

    'target_fps' => (float) env('GAME_ENGINE_TARGET_FPS', 60),

    'physics_tps' => (float) env('GAME_ENGINE_PHYSICS_TPS', 60),

    /*
    |--------------------------------------------------------------------------
    | Concurrency
    |--------------------------------------------------------------------------
    |
    | Fiber-backed process fan-out. Sync always works as a fallback.
    |
    */

    'concurrency' => env('GAME_ENGINE_CONCURRENCY', 'fiber'),

    /*
    |--------------------------------------------------------------------------
    | Resource store (SQLite texture / asset cache)
    |--------------------------------------------------------------------------
    */

    'resource_store' => [
        'driver' => env('GAME_ENGINE_RESOURCE_STORE', 'sqlite'),
        'database' => env('GAME_ENGINE_RESOURCE_DB'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis (optional deferred signal inbox)
    |--------------------------------------------------------------------------
    */

    'redis' => [
        'enabled' => (bool) env('GAME_ENGINE_REDIS', false),
        'connection' => env('GAME_ENGINE_REDIS_CONNECTION', 'default'),
    ],

];
