<?php

namespace ScrapyardIO\GameEngine\Nodes\Control;

use ScrapyardIO\UX\Geometry\Rect;

class VideoStreamPlayer extends Control
{
    public bool $playing = false;

    public bool $paused = false;

    public bool $autoplay = false;

    public float $volumeDb = 0.0;

    public float $position = 0.0;

    protected ?string $streamPath = null;

    public function __construct(string $name = '', ?Rect $rect = null)
    {
        parent::__construct($name, $rect ?? new Rect(0, 0, 160, 90));
    }

    public function setStream(?string $path): static
    {
        $this->streamPath = $path;

        return $this;
    }

    public function play(): static
    {
        $this->playing = true;
        $this->paused = false;

        return $this;
    }

    public function stop(): static
    {
        $this->playing = false;
        $this->paused = false;
        $this->position = 0.0;

        return $this;
    }

    public function isPlaying(): bool
    {
        return $this->playing && ! $this->paused;
    }
}
