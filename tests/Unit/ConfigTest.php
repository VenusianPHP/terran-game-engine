<?php
declare(strict_types=1);

it('ships a default config with one staged Velvet window and 60 Hz physics', function () {
    if (! function_exists('env')) {
        function env(string $key, mixed $default = null): mixed { return $_ENV[$key] ?? $default; }
    }
    $config = require dirname(__DIR__, 2).'/config/terran.php';

    expect($config['main_scene'])->toBeNull()
        ->and($config['refresh_rate'])->toBeNull()
        ->and($config['physics_hz'])->toBe(60.0)
        ->and($config['max_physics_steps'])->toBe(8)
        ->and($config['stager'])->toBeNull()
        ->and($config['autoloads'])->toBe([])
        ->and($config['input'])->toBe(['actions' => [], 'events' => ['keyboard', 'mouse'], 'seats' => 4, 'keyboard_seat' => 1, 'seat_on_connect' => true])
        ->and($config['targets'])->toBe(['main' => ['kind' => 'staged', 'width' => 960, 'height' => 540, 'title' => 'Terran', 'engine' => 'velvet', 'options' => []]]);
});
