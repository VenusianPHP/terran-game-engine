<?php
declare(strict_types=1);
namespace Terran\Assets;

/** One named animation: cells of a sheet at a rate. */
final readonly class Animation
{
    /** @param list<int> $cells */
    public function __construct(public SpriteSheet $sheet, public array $cells, public float $fps, public bool $loop) {}
}
