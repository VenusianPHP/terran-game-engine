<?php
declare(strict_types=1);
namespace Terran\Assets;

use Surface\Contracts\Framebuffers\Framebuffer;
use Terran\TerranException;

/**
 * A texture cut into cells, one framebuffer each, once: the engine's image()
 * draws whole framebuffers only. Cutting copies RGBA8 rows, so alpha survives.
 */
final class SpriteSheet
{
    /** @param list<Framebuffer> $cells */
    private function __construct(public readonly Texture $texture, private readonly array $cells) {}

    public static function grid(Texture $texture, int $columns, int $rows, ?AssetCache $cache = null): self
    {
        if ($columns < 1 || $rows < 1) {
            throw new TerranException("A sprite grid has at least 1 column and 1 row; got {$columns}×{$rows}.");
        }
        [$w, $h] = [$texture->width(), $texture->height()];
        if ($w % $columns !== 0 || $h % $rows !== 0) {
            throw new TerranException("The texture is {$w}×{$h}, which a {$columns}×{$rows} grid does not divide evenly.");
        }
        [$cw, $ch] = [intdiv($w, $columns), intdiv($h, $rows)];
        $rects = [];
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $columns; $col++) {
                $rects[] = [$col * $cw, $row * $ch, $cw, $ch];
            }
        }

        return new self($texture, self::cut($texture, $rects, $cache ?? app('terran.assets')));
    }

    /** @param list<array{int, int, int, int}> $regions [x, y, width, height] each */
    public static function regions(Texture $texture, array $regions, ?AssetCache $cache = null): self
    {
        [$w, $h] = [$texture->width(), $texture->height()];
        foreach ($regions as [$x, $y, $rw, $rh]) {
            if ($rw < 1 || $rh < 1 || $x < 0 || $y < 0 || $x + $rw > $w || $y + $rh > $h) {
                throw new TerranException("Region [{$x}, {$y}, {$rw}, {$rh}] lies outside the {$w}×{$h} texture.");
            }
        }

        return new self($texture, self::cut($texture, array_values($regions), $cache ?? app('terran.assets')));
    }

    public function cell(int $index): Framebuffer
    {
        return $this->cells[$index] ?? throw new TerranException("Cell {$index} is outside this sheet's cells 0 to ".(count($this->cells) - 1).'.');
    }

    public function count(): int { return count($this->cells); }

    /**
     * @param list<array{int, int, int, int}> $rects
     * @return list<Framebuffer>
     */
    private static function cut(Texture $texture, array $rects, AssetCache $cache): array
    {
        $rgba = $texture->framebuffer()->toRgba8();
        $stride = $texture->width() * 4;
        $cells = [];
        foreach ($rects as [$x, $y, $w, $h]) {
            $bytes = '';
            for ($row = 0; $row < $h; $row++) {
                $bytes .= substr($rgba, ($y + $row) * $stride + $x * 4, $w * 4);
            }
            $cells[] = $cache->blank($w, $h)->writeRgba8($bytes, $w, $h);
        }

        return $cells;
    }
}
