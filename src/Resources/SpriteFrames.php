<?php

namespace ScrapyardIO\GameEngine\Resources;

class SpriteFrames
{
    /**
     * @var array<string, list<Texture>>
     */
    protected array $animations = [];

    /**
     * @param  list<Texture>  $frames
     */
    public function addAnimation(string $name, array $frames): static
    {
        $this->animations[$name] = array_values($frames);

        return $this;
    }

    public function hasAnimation(string $name): bool
    {
        return isset($this->animations[$name]) && $this->animations[$name] !== [];
    }

    public function frame(string $animation, int $index): ?Texture
    {
        $frames = $this->animations[$animation] ?? [];

        return $frames[$index] ?? null;
    }

    public function frameCount(string $animation): int
    {
        return count($this->animations[$animation] ?? []);
    }
}
