<?php
declare(strict_types=1);
namespace Tests\Fixtures;

use Terran\Input\Events\InputEvent;
use Terran\Scene;

/** A plain Scene that records the input reaching it. */
final class InputSpy extends Scene
{
    /** @var list<class-string> */
    public array $got = [];

    public function input(InputEvent $event): bool
    {
        $this->got[] = $event::class;

        return false;
    }
}
