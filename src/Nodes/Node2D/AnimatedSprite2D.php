<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\SpriteFrames;
use ScrapyardIO\GameEngine\Resources\Texture;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Point;

class AnimatedSprite2D extends Node2D
{
    protected ?SpriteFrames $frames = null;
    protected string $animation = 'default';
    protected int $frame = 0;
    protected float $speedScale = 1.0;
    protected float $accum = 0.0;
    protected bool $playing = true;

    public function setSpriteFrames(?SpriteFrames $frames): static
    {
        $this->frames = $frames;

        return $this;
    }

    public function play(string $animation = 'default'): static
    {
        $this->animation = $animation;
        $this->playing = true;
        $this->frame = 0;

        return $this;
    }

    public function process(float $dt): void
    {
        if (! $this->playing || is_null($this->frames) || ! $this->frames->hasAnimation($this->animation)) {
            return;
        }

        $this->accum += $dt * $this->speedScale;
        $fps = 12.0;

        while ($this->accum >= 1.0 / $fps) {
            $this->accum -= 1.0 / $fps;
            $count = $this->frames->frameCount($this->animation);
            $this->frame = $count > 0 ? ($this->frame + 1) % $count : 0;
            $tex = $this->frames->frame($this->animation, $this->frame);

            if ($tex instanceof Texture) {
                $this->width = $tex->width();
                $this->height = $tex->height();
            }
        }
    }

    protected function onDraw(PaintContext $ctx): void
    {
        if (is_null($this->frames)) {
            return;
        }

        $tex = $this->frames->frame($this->animation, $this->frame);

        if (is_null($tex)) {
            return;
        }

        $ctx->withOrigin(new Point((int) round($this->globalX()), (int) round($this->globalY())))
            ->fillRectLocal(0, 0, $tex->width(), $tex->height(), 0xFFFFFFFF);
    }
}