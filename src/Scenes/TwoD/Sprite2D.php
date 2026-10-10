<?php
declare(strict_types=1);
namespace Terran\Scenes\TwoD;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\Contracts\Framebuffers\Framebuffer;
use Terran\Assets\SpriteSheet;
use Terran\Assets\Texture;
use Terran\Math\Vector2;
use Terran\TerranException;

/** A texture, or one cell of a sheet chosen by frame. */
class Sprite2D extends Scene2D
{
    use DrawsImage;

    public ?Texture $texture = null;

    public ?SpriteSheet $sheet = null;

    public int $frame = 0;

    public function __construct(string $name, Texture|SpriteSheet|null $source = null)
    {
        parent::__construct($name);
        $this->offset = Vector2::zero();
        if ($source instanceof Texture) {
            $this->texture = $source;
        } elseif ($source instanceof SpriteSheet) {
            $this->sheet = $source;
        }
    }

    public function image(): ?Framebuffer
    {
        if (! is_null($this->texture) && ! is_null($this->sheet)) {
            throw new TerranException("Sprite2D '{$this->name}' has both a texture and a sheet; set one.");
        }
        if (! is_null($this->sheet)) {
            return $this->sheet->cell($this->frame);
        }

        return $this->texture?->framebuffer();
    }

    public function draw(RenderingEngine $ge): void
    {
        $this->drawImage($ge, $this->image());
    }
}
