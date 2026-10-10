<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\NutsAndBolts\Color;
use Terran\Math\Vector2;
use Terran\Scenes\Align;
use Terran\Scenes\ResolvesFont;
use Terran\Scenes\VAlign;
use Terran\TerranException;

/** Bitmap text placed inside its rect; its minimum size is its text's ink box. */
class Label extends UIControl
{
    use ResolvesFont;

    public Color $color;

    public int $size = 1;

    public Align $align = Align::LEFT;

    public VAlign $valign = VAlign::TOP;

    public function __construct(string $name, public string $text = '', ?Color $color = null)
    {
        parent::__construct($name);
        $this->color = $color ?? Color::hex('#FFFFFF');
        $this->mouseFilter = MouseFilter::IGNORE;
    }

    protected function contentMinSize(RenderingEngine $ge): Vector2
    {
        if ($this->text === '') {
            return Vector2::zero();
        }
        [, , $w, $h] = $ge->textBounds($this->text, $this->face());

        return new Vector2($w * $this->checkedSize(), $h * $this->checkedSize());
    }

    public function draw(RenderingEngine $ge): void
    {
        if ($this->text === '') {
            return;
        }
        $face = $this->face();
        $scale = $this->checkedSize();
        [$bx, $by, $bw, $bh] = $ge->textBounds($this->text, $face);
        $box = $this->size();
        [$tw, $th] = [$bw * $scale, $bh * $scale];
        $x = match ($this->align) {
            Align::LEFT => 0.0,
            Align::CENTER => ($box->x - $tw) / 2,
            Align::RIGHT => $box->x - $tw,
        };
        $y = match ($this->valign) {
            VAlign::TOP => 0.0,
            VAlign::MIDDLE => ($box->y - $th) / 2,
            VAlign::BOTTOM => $box->y - $th,
        };
        $ge->translate($x, $y);
        $ge->scale((float) $scale);
        $ge->text($this->text, -$bx, -$by, $this->tint($this->color), $face);
    }

    private function checkedSize(): int
    {
        if ($this->size < 1) {
            throw new TerranException("Label '{$this->name}' size is 1 or more; got {$this->size}.");
        }

        return $this->size;
    }
}
