<?php
declare(strict_types=1);
namespace Tests\Fixtures;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;
use Terran\Scenes\TwoD\Scene2D;

/** A filled rectangle from its origin, for pixel tests. */
final class Block2D extends Scene2D
{
    public function __construct(string $name, public float $w = 1.0, public float $h = 1.0, public string $hex = '#FF0000')
    {
        parent::__construct($name);
    }

    public function draw(RenderingEngine $ge): void
    {
        $ge->fillRect(0.0, 0.0, $this->w, $this->h, $this->tint(Color::hex($this->hex)));
    }
}
