<?php
declare(strict_types=1);
namespace Terran\Assets;

use Terran\TerranException;

/** Named animations for an AnimatedSprite2D. */
final class SpriteFrames
{
    /** @var array<string, Animation> */
    private array $animations = [];

    /** @param list<int> $cells */
    public function add(string $name, SpriteSheet $sheet, array $cells, float $fps = 10.0, bool $loop = true): static
    {
        if ($cells === []) {
            throw new TerranException("Animation '{$name}' needs at least one cell.");
        }
        foreach ($cells as $cell) {
            if ($cell < 0 || $cell >= $sheet->count()) {
                throw new TerranException("Animation '{$name}' names cell {$cell}; its sheet has cells 0 to ".($sheet->count() - 1).'.');
            }
        }
        if ($fps <= 0.0) {
            throw new TerranException("Animation '{$name}' fps is a positive number; got {$fps}.");
        }
        $this->animations[$name] = new Animation($sheet, array_values($cells), $fps, $loop);

        return $this;
    }

    public function animation(string $name): Animation
    {
        return $this->animations[$name] ?? throw new TerranException("No animation '{$name}'. Animations: ".($this->animations === [] ? 'none' : implode(', ', array_keys($this->animations))).'.');
    }

    public function has(string $name): bool { return isset($this->animations[$name]); }

    /** @return list<string> */
    public function names(): array { return array_keys($this->animations); }
}
