<?php
declare(strict_types=1);
namespace Terran\Assets;

use Surface\Contracts\Framebuffers\Framebuffer;
use Terran\TerranException;

/** A sheet of tiles; a tile id is a sheet cell index. */
final readonly class TileSet
{
    public function __construct(public SpriteSheet $sheet, public int $tileWidth, public int $tileHeight)
    {
        if ($tileWidth < 1 || $tileHeight < 1) {
            throw new TerranException("A tile size is at least 1×1; got {$tileWidth}×{$tileHeight}.");
        }
    }

    public function tile(int $id): Framebuffer { return $this->sheet->cell($id); }

    public function count(): int { return $this->sheet->count(); }
}
