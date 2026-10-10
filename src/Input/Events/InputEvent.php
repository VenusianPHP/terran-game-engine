<?php
declare(strict_types=1);
namespace Terran\Input\Events;

use Voyager\Contracts\Signals\NamedSignal;

/** One thing that happened to input this frame. Scenes get it through input(); the application hears it on app('signals') as terran.input.<kind>. */
abstract readonly class InputEvent implements NamedSignal {}
