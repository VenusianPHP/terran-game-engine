<?php
declare(strict_types=1);
namespace Tests\Fixtures;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;
use Terran\Scene;

/** Records every hook call as [hook, argument]; draws a 2×2 white square at (1,1) when asked. */
class RecordingScene extends Scene
{
    /** @var list<array{0: string, 1: mixed}> */
    public array $calls = [];

    public bool $draws = false;

    public function ready(): void { $this->calls[] = ['ready', null]; }

    public function process(float $delta): void { $this->calls[] = ['process', $delta]; }

    public function physicsProcess(float $delta): void { $this->calls[] = ['physicsProcess', $delta]; }

    public function exit(): void { $this->calls[] = ['exit', null]; }

    public function draw(RenderingEngine $ge): void
    {
        $this->calls[] = ['draw', null];
        if ($this->draws) {
            $ge->fillRect(1, 1, 2, 2, Color::rgb(255, 255, 255));
        }
    }

    /** @return list<string> hook names in call order */
    public function hooks(): array { return array_column($this->calls, 0); }
}
