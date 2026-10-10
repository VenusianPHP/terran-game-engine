<?php
declare(strict_types=1);
namespace Terran\Scenes\TwoD;

use Surface\Contracts\Drawing\RenderingEngine;
use Surface\Contracts\Framebuffers\Framebuffer;
use Terran\Assets\Animation;
use Terran\Assets\SpriteFrames;
use Terran\Interrupts\Interrupt;
use Terran\Math\Vector2;
use Terran\TerranException;

/**
 * Plays named animations from SpriteFrames. process() advances by
 * fps × speed × delta; a non-looping animation stops on its last frame and
 * emits animationFinished once; play() on it again replays from frame 0. A subclass that overrides process() calls
 * parent::process().
 */
#[Interrupt('animationFinished', ['string'])]
class AnimatedSprite2D extends Scene2D
{
    use DrawsImage;

    public int $frame = 0;

    public bool $playing = false;

    /** 0 or more; 1 = the animation's own fps. */
    public float $speed = 1.0;

    private float $elapsed = 0.0;

    public function __construct(string $name, public ?SpriteFrames $frames = null, public ?string $animation = null)
    {
        parent::__construct($name);
        $this->offset = Vector2::zero();
    }

    public function play(?string $name = null): static
    {
        $name ??= $this->animation ?? throw new TerranException("AnimatedSprite2D '{$this->name}' has no animation to play; pass a name.");
        $animation = $this->frames()->animation($name);
        $finished = ! $this->playing && ! $animation->loop && $this->frame >= count($animation->cells) - 1;
        if ($name !== $this->animation || $finished) {
            $this->animation = $name;
            $this->frame = 0;
            $this->elapsed = 0.0;
        }
        $this->playing = true;

        return $this;
    }

    public function stop(): static
    {
        $this->playing = false;

        return $this;
    }

    public function process(float $delta): void
    {
        if (! $this->playing || is_null($this->animation)) {
            return;
        }
        if ($this->speed < 0.0) {
            throw new TerranException("AnimatedSprite2D '{$this->name}' speed is 0 or more; got {$this->speed}.");
        }
        $animation = $this->current();
        $step = 1.0 / $animation->fps;
        $this->elapsed += $delta * $this->speed;
        while ($this->elapsed >= $step) {
            $this->elapsed -= $step;
            if ($this->frame < count($animation->cells) - 1) {
                $this->frame++;
            } elseif ($animation->loop) {
                $this->frame = 0;
            } else {
                $this->playing = false;
                $this->elapsed = 0.0;
                $this->emit('animationFinished', $this->animation);

                return;
            }
        }
    }

    public function image(): ?Framebuffer
    {
        if (is_null($this->animation)) {
            return null;
        }
        $animation = $this->current();

        return $animation->sheet->cell($animation->cells[min($this->frame, count($animation->cells) - 1)]);
    }

    public function draw(RenderingEngine $ge): void
    {
        $this->drawImage($ge, $this->image());
    }

    private function frames(): SpriteFrames
    {
        return $this->frames ?? throw new TerranException("AnimatedSprite2D '{$this->name}' has no SpriteFrames.");
    }

    private function current(): Animation
    {
        return $this->frames()->animation((string) $this->animation);
    }
}
