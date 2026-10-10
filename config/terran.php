<?php

return [

    /*
    | The Scene class php rocket game roots. Null until the application sets one;
    | the engine says so when asked to run without it.
    */
    'main_scene' => env('TERRAN_MAIN_SCENE'),

    /*
    | Render frames per second. Null defers to sketches.refresh_rate.
    */
    'refresh_rate' => env('TERRAN_REFRESH_RATE') === null ? null : (float) env('TERRAN_REFRESH_RATE'),

    /*
    | Fixed physics steps per second on the GameLoop. physicsProcess() receives
    | 1 / physics_hz every call. The loop paces at io-pools.event_loop.pace_ms,
    | so 60 is the practical ceiling for one step per fire.
    */
    'physics_hz' => (float) env('TERRAN_PHYSICS_HZ', 60),

    /*
    | The most physics steps one advance runs. After a stall longer than this
    | many steps, the rest of the stall is dropped rather than replayed.
    */
    'max_physics_steps' => (int) env('TERRAN_MAX_PHYSICS_STEPS', 8),

    /*
    | Autoloads every Scene can reach with autoload(), as class => alias (null
    | for a service, or a Scene added under the root as its lower-cased short
    | class name). Scenes declare their own with #[Autoload].
    */
    'autoloads' => [],

    /*
    |--------------------------------------------------------------------------
    | Stager
    |--------------------------------------------------------------------------
    |
    | The toolkit that opens every 'staged' target's window, unless the target
    | names its own under options.toolkit. Supported: "appkit" (macOS),
    | "sdl3" (macOS, Linux) and "glfw" (macOS, Linux). The stager's package
    | must be installed. Null leaves the choice to Surface's own default
    | for this OS (bridge.stage).
    |
    */

    'stager' => env('TERRAN_STAGER'),

    /*
    | Output targets the engine opens at boot, by name. 'kind' picks the opener:
    | 'staged' (a staged window through staged-windows), 'canvas' (a TKCanvas the
    | application passes in under 'canvas'), 'display' (a panel through displays:
    | 'panel', 'config', 'direct'). 'engine' names a rendering engine the drawing
    | manager knows; absent, the drawing default. 'options' go to the opener.
    */
    'targets' => [
        'main' => [
            'kind' => 'staged',
            'width' => 960,
            'height' => 540,
            'title' => 'Terran',
            'engine' => 'velvet',
            'options' => [],
        ],
    ],

    /*
    | Input. 'actions' names actions over bindings: key:<Key>, mouse:<MouseButton>,
    | pad:<GamepadButton>, axis:<GamepadAxis>:+|-[:dead zone], values as
    | HumanInput's enums spell them. 'events' are the devices read for input()
    | while a live Scene overrides it: 'keyboard', 'mouse', 'pads'. A device
    | nothing uses is never read.
    */
    'input' => [
        'actions' => [],
        'events' => ['keyboard', 'mouse'],

        /*
        | Seats: how many pads are seated at once (0 turns seating off, and
        | every event carries seat null). A pad keeps its seat across a
        | reconnect by its hardware id (by its name when it has none) and is
        | lit as that player. Keyboard and
        | mouse belong to 'keyboard_seat' (null for no seat). With
        | 'seat_on_connect' false, the game seats pads itself through
        | Terran\Input\Seats::assign(), for a lobby.
        */
        'seats' => (int) env('TERRAN_SEATS', 4),
        'keyboard_seat' => 1,
        'seat_on_connect' => true,
    ],

];
