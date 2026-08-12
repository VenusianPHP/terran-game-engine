<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class AudioStreamPlayer2D extends Node2D
{
    public bool $playing = false;
    public float $volumeDb = 0.0;
    public float $maxDistance = 2000.0;

    public function play(): static
    {
        $this->playing = true;

        return $this;
    }

    public function stop(): static
    {
        $this->playing = false;

        return $this;
    }
}