<?php
declare(strict_types=1);
namespace Tests\Fixtures;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;
use Terran\Input\Events\InputEvent;
use Terran\Scenes\UI\UIControl;

/** Fills its rect and records the input it receives. */
final class RecordingControl extends UIControl
{
    /** @var list<class-string> */
    public array $got = [];

    public bool $handles = false;

    public int $entered = 0;

    public int $exited = 0;

    public function __construct(string $name, public string $hex = '#FFFFFF')
    {
        parent::__construct($name);
    }

    public function input(InputEvent $event): bool
    {
        $this->got[] = $event::class;

        return $this->handles;
    }

    public function mouseEntered(): void { $this->entered++; }

    public function mouseExited(): void { $this->exited++; }

    public function draw(RenderingEngine $ge): void
    {
        $size = $this->size();
        $ge->fillRect(0.0, 0.0, $size->x, $size->y, $this->tint(Color::hex($this->hex)));
    }
}
