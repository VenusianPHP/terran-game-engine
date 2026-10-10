<?php
declare(strict_types=1);
namespace Terran\Scenes\TwoD;

use Surface\Contracts\Drawing\RenderingEngine;
use Terran\Assets\TileSet;
use Terran\Math\Vector2;
use Terran\TerranException;

/** A grid of tile ids over a TileSet. draw() visits only the cells inside the engine's visible area. */
class TileMap extends Scene2D
{
    /** @var array<int, array<int, int>> row → col → tile id */
    private array $cells = [];

    public function __construct(string $name, public ?TileSet $tileSet = null)
    {
        parent::__construct($name);
    }

    /** null clears the cell. */
    public function setCell(int $col, int $row, ?int $id): static
    {
        if (is_null($id)) {
            unset($this->cells[$row][$col]);
            if (($this->cells[$row] ?? null) === []) {
                unset($this->cells[$row]);
            }

            return $this;
        }
        if (! is_null($this->tileSet) && ($id < 0 || $id >= $this->tileSet->count())) {
            throw new TerranException("TileMap '{$this->name}' has no tile {$id}; its TileSet has tiles 0 to ".($this->tileSet->count() - 1).'.');
        }
        $this->cells[$row][$col] = $id;

        return $this;
    }

    public function cell(int $col, int $row): ?int { return $this->cells[$row][$col] ?? null; }

    /** @return array{int, int} [col, row] of a point in this map's local space */
    public function cellAt(Vector2 $local): array
    {
        $set = $this->set();

        return [(int) floor($local->x / $set->tileWidth), (int) floor($local->y / $set->tileHeight)];
    }

    /** @return array{int, int, int, int} [col, row, cols, rows] of the set cells; all zero when none are */
    public function usedRect(): array
    {
        if ($this->cells === []) {
            return [0, 0, 0, 0];
        }
        $rows = array_keys($this->cells);
        $cols = array_merge(...array_map('array_keys', array_values($this->cells)));

        return [min($cols), min($rows), max($cols) - min($cols) + 1, max($rows) - min($rows) + 1];
    }

    public function clear(): static
    {
        $this->cells = [];

        return $this;
    }

    public function draw(RenderingEngine $ge): void
    {
        if ($this->cells === []) {
            return;
        }
        $set = $this->set();
        $inverse = $ge->matrix()->inverse();
        if (is_null($inverse)) {
            return;
        }
        $corners = [$inverse->apply(0.0, 0.0), $inverse->apply((float) $ge->width(), 0.0), $inverse->apply(0.0, (float) $ge->height()), $inverse->apply((float) $ge->width(), (float) $ge->height())];
        $xs = array_column($corners, 0);
        $ys = array_column($corners, 1);
        [$col0, $col1] = [(int) floor(min($xs) / $set->tileWidth), (int) floor(max($xs) / $set->tileWidth)];
        [$row0, $row1] = [(int) floor(min($ys) / $set->tileHeight), (int) floor(max($ys) / $set->tileHeight)];
        $opacity = $this->effectiveModulate();
        foreach ($this->cells as $row => $cols) {
            if ($row < $row0 || $row > $row1) {
                continue;
            }
            foreach ($cols as $col => $id) {
                if ($col >= $col0 && $col <= $col1) {
                    $ge->image($set->tile($id), (float) ($col * $set->tileWidth), (float) ($row * $set->tileHeight), null, null, $opacity);
                }
            }
        }
    }

    private function set(): TileSet
    {
        return $this->tileSet ?? throw new TerranException("TileMap '{$this->name}' has no TileSet; set one before reading or drawing cells.");
    }
}
