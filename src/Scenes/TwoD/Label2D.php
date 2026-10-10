<?php
declare(strict_types=1);
namespace Terran\Scenes\TwoD;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;
use Terran\Scenes\Align;
use Terran\Scenes\ResolvesFont;
use Terran\TerranException;

/**
 * Bitmap text in the world. The origin is the top edge of the textBounds()
 * box: its left end (LEFT), middle (CENTER) or right end (RIGHT). size is a
 * whole-pixel scale.
 */
class Label2D extends Scene2D
{
    use ResolvesFont;

    public Color $color;

    public int $size = 1;

    public Align $align = Align::LEFT;

    public function __construct(string $name, public string $text = '', ?Color $color = null)
    {
        parent::__construct($name);
        $this->color = $color ?? Color::hex('#FFFFFF');
    }

    public function draw(RenderingEngine $ge): void
    {
        if ($this->size < 1) {
            throw new TerranException("Label2D '{$this->name}' size is 1 or more; got {$this->size}.");
        }
        if ($this->text === '') {
            return;
        }
        $face = $this->face();
        [$bx, $by, $bw] = $ge->textBounds($this->text, $face);
        $x = match ($this->align) {
            Align::LEFT => -$bx,
            Align::CENTER => -$bx - $bw / 2,
            Align::RIGHT => -$bx - $bw,
        };
        $ge->scale((float) $this->size);
        $ge->text($this->text, $x, -$by, $this->tint($this->color), $face);
    }
}
