#!/usr/bin/env php
<?php

/**
 * Generates remaining Godot Node2D / Control catalog classes (idempotent).
 */

$root = dirname(__DIR__);

function writeIfMissing(string $path, string $contents): void
{
    if (file_exists($path)) {
        echo "skip $path\n";

        return;
    }

    $dir = dirname($path);

    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($path, $contents);
    echo "write $path\n";
}

$node2d = [
    'AnimatedSprite2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

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
PHP,
    'Camera2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class Camera2D extends Node2D
{
    public bool $enabled = true;
    public float $zoomX = 1.0;
    public float $zoomY = 1.0;
    public int $offsetX = 0;
    public int $offsetY = 0;

    public function getScreenCenter(): array
    {
        return [
            (int) round($this->globalX()) + $this->offsetX,
            (int) round($this->globalY()) + $this->offsetY,
        ];
    }
}
PHP,
    'Marker2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class Marker2D extends Node2D
{
    //
}
PHP,
    'Line2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Point;

class Line2D extends Node2D
{
    /** @var list<array{0:float,1:float}> */
    public array $points = [];
    public int $defaultColor = 0xFFFFFFFF;
    public float $width = 1.0;

    public function addPoint(float $x, float $y): static
    {
        $this->points[] = [$x, $y];

        return $this;
    }

    protected function onDraw(PaintContext $ctx): void
    {
        $ox = (int) round($this->globalX());
        $oy = (int) round($this->globalY());
        $local = $ctx->withOrigin(new Point($ox, $oy));

        for ($i = 0; $i < count($this->points) - 1; $i++) {
            [$x0, $y0] = $this->points[$i];
            [$x1, $y1] = $this->points[$i + 1];
            $local->drawLineLocal((int) $x0, (int) $y0, (int) $x1, (int) $y1, $this->defaultColor);
        }
    }
}
PHP,
    'Polygon2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Point;

class Polygon2D extends Node2D
{
    /** @var list<array{0:float,1:float}> */
    public array $polygon = [];
    public int $color = 0xFFFFFFFF;

    protected function onDraw(PaintContext $ctx): void
    {
        if (count($this->polygon) < 3) {
            return;
        }

        $xs = array_column($this->polygon, 0);
        $ys = array_column($this->polygon, 1);
        $minX = (int) floor(min($xs));
        $minY = (int) floor(min($ys));
        $maxX = (int) ceil(max($xs));
        $maxY = (int) ceil(max($ys));

        $ctx->withOrigin(new Point((int) round($this->globalX()), (int) round($this->globalY())))
            ->fillRectLocal($minX, $minY, max(1, $maxX - $minX), max(1, $maxY - $minY), $this->color);
    }
}
PHP,
    'RayCast2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class RayCast2D extends Node2D
{
    public float $targetX = 0.0;
    public float $targetY = 50.0;
    public bool $enabled = true;
    public bool $collideWithBodies = true;
    protected bool $colliding = false;
    protected ?CollisionObject2D $collider = null;

    public function isColliding(): bool
    {
        return $this->colliding;
    }

    public function getCollider(): ?CollisionObject2D
    {
        return $this->collider;
    }

    /**
     * @param  list<CollisionObject2D>  $candidates
     */
    public function forceRaycastUpdate(array $candidates): void
    {
        $this->colliding = false;
        $this->collider = null;

        if (! $this->enabled) {
            return;
        }

        $x0 = $this->globalX();
        $y0 = $this->globalY();
        $x1 = $x0 + $this->targetX;
        $y1 = $y0 + $this->targetY;

        foreach ($candidates as $body) {
            if ($body === $this) {
                continue;
            }

            $bx = $body->globalX();
            $by = $body->globalY();

            if (min($x0, $x1) <= $bx + $body->width
                && max($x0, $x1) >= $bx
                && min($y0, $y1) <= $by + $body->height
                && max($y0, $y1) >= $by) {
                $this->colliding = true;
                $this->collider = $body;

                return;
            }
        }
    }
}
PHP,
    'ShapeCast2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\Shapes\Shape2D;

class ShapeCast2D extends Node2D
{
    protected ?Shape2D $shape = null;
    public float $targetX = 0.0;
    public float $targetY = 50.0;
    public bool $enabled = true;
    protected bool $colliding = false;

    public function setShape(?Shape2D $shape): static
    {
        $this->shape = $shape;

        return $this;
    }

    public function isColliding(): bool
    {
        return $this->colliding;
    }

    /** @param  list<CollisionObject2D>  $candidates */
    public function forceShapecastUpdate(array $candidates): void
    {
        $this->colliding = false;
        $w = is_null($this->shape) ? 8 : (int) $this->shape->width();
        $h = is_null($this->shape) ? 8 : (int) $this->shape->height();
        $x = $this->globalX() + $this->targetX;
        $y = $this->globalY() + $this->targetY;

        foreach ($candidates as $body) {
            if ($x < $body->globalX() + $body->width
                && $x + $w > $body->globalX()
                && $y < $body->globalY() + $body->height
                && $y + $h > $body->globalY()) {
                $this->colliding = true;

                return;
            }
        }
    }
}
PHP,
    'CollisionShape2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\Shapes\Shape2D;

class CollisionShape2D extends Node2D
{
    protected ?Shape2D $shape = null;
    public bool $disabled = false;

    public function setShape(?Shape2D $shape): static
    {
        $this->shape = $shape;

        if (! is_null($shape)) {
            $this->width = (int) ceil($shape->width());
            $this->height = (int) ceil($shape->height());
        }

        return $this;
    }

    public function shape(): ?Shape2D
    {
        return $this->shape;
    }
}
PHP,
    'CollisionPolygon2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class CollisionPolygon2D extends Node2D
{
    /** @var list<array{0:float,1:float}> */
    public array $polygon = [];
    public bool $disabled = false;
}
PHP,
    'Path2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\Curve2D;

class Path2D extends Node2D
{
    protected ?Curve2D $curve = null;

    public function setCurve(?Curve2D $curve): static
    {
        $this->curve = $curve;

        return $this;
    }

    public function curve(): ?Curve2D
    {
        return $this->curve;
    }
}
PHP,
    'PathFollow2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class PathFollow2D extends Node2D
{
    public float $progress = 0.0;
    public float $progressRatio = 0.0;
    public bool $rotates = true;

    public function process(float $dt): void
    {
        $parent = $this->parent();

        if (! $parent instanceof Path2D || is_null($parent->curve())) {
            return;
        }

        $point = $parent->curve()->sample($this->progressRatio);
        $this->positionX = $point[0];
        $this->positionY = $point[1];
    }
}
PHP,
    'Parallax2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class Parallax2D extends Node2D
{
    public float $scrollScaleX = 1.0;
    public float $scrollScaleY = 1.0;
}
PHP,
    'ParallaxLayer' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class ParallaxLayer extends Node2D
{
    public float $motionScaleX = 1.0;
    public float $motionScaleY = 1.0;
    public float $motionOffsetX = 0.0;
    public float $motionOffsetY = 0.0;
}
PHP,
    'RemoteTransform2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class RemoteTransform2D extends Node2D
{
    protected ?Node2D $remote = null;
    public bool $updatePosition = true;
    public bool $updateRotation = true;
    public bool $updateScale = true;

    public function setRemote(?Node2D $remote): static
    {
        $this->remote = $remote;

        return $this;
    }

    public function process(float $dt): void
    {
        if (is_null($this->remote)) {
            return;
        }

        if ($this->updatePosition) {
            $this->remote->positionX = $this->globalX();
            $this->remote->positionY = $this->globalY();
        }

        if ($this->updateRotation) {
            $this->remote->rotation = $this->rotation;
        }

        if ($this->updateScale) {
            $this->remote->scaleX = $this->scaleX;
            $this->remote->scaleY = $this->scaleY;
        }
    }
}
PHP,
    'VisibleOnScreenNotifier2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Signals\Signal;

#[Signal('screen_entered')]
#[Signal('screen_exited')]
class VisibleOnScreenNotifier2D extends Node2D
{
    protected bool $onScreen = false;

    public function updateVisibility(int $viewW, int $viewH): void
    {
        $rect = $this->worldRect();
        $visible = ! is_null($rect)
            && $rect->x < $viewW
            && $rect->right() > 0
            && $rect->y < $viewH
            && $rect->bottom() > 0;

        if ($visible && ! $this->onScreen) {
            $this->onScreen = true;
            $this->emit('screen_entered');
        } elseif (! $visible && $this->onScreen) {
            $this->onScreen = false;
            $this->emit('screen_exited');
        }
    }

    public function isOnScreen(): bool
    {
        return $this->onScreen;
    }
}
PHP,
    'VisibleOnScreenEnabler2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class VisibleOnScreenEnabler2D extends VisibleOnScreenNotifier2D
{
    public function updateVisibility(int $viewW, int $viewH): void
    {
        parent::updateVisibility($viewW, $viewH);
        $this->setVisible($this->isOnScreen());
    }
}
PHP,
    'TouchScreenButton' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Signals\Signal;

#[Signal('pressed')]
#[Signal('released')]
class TouchScreenButton extends Node2D
{
    public string $action = '';
    protected bool $pressed = false;

    public function setPressed(bool $pressed): static
    {
        if ($pressed === $this->pressed) {
            return $this;
        }

        $this->pressed = $pressed;
        $this->emit($pressed ? 'pressed' : 'released');

        return $this;
    }

    public function isPressed(): bool
    {
        return $this->pressed;
    }
}
PHP,
    'CPUParticles2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Point;

class CPUParticles2D extends Node2D
{
    public int $amount = 8;
    public float $lifetime = 1.0;
    public bool $emitting = true;
    /** @var list<array{x:float,y:float,life:float,vx:float,vy:float}> */
    protected array $particles = [];

    public function process(float $dt): void
    {
        if ($this->emitting && count($this->particles) < $this->amount) {
            $this->particles[] = [
                'x' => 0.0,
                'y' => 0.0,
                'life' => $this->lifetime,
                'vx' => (mt_rand(-50, 50)),
                'vy' => (mt_rand(-80, -20)),
            ];
        }

        foreach ($this->particles as $i => $p) {
            $p['life'] -= $dt;
            $p['x'] += $p['vx'] * $dt;
            $p['y'] += $p['vy'] * $dt;

            if ($p['life'] <= 0) {
                unset($this->particles[$i]);
            } else {
                $this->particles[$i] = $p;
            }
        }

        $this->particles = array_values($this->particles);
    }

    protected function onDraw(PaintContext $ctx): void
    {
        $local = $ctx->withOrigin(new Point((int) round($this->globalX()), (int) round($this->globalY())));

        foreach ($this->particles as $p) {
            $local->fillRectLocal((int) $p['x'], (int) $p['y'], 2, 2, 0xFFFFFFFF);
        }
    }
}
PHP,
    'GPUParticles2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

/** Shares CPU particle simulation until a GPU companion exists. */
class GPUParticles2D extends CPUParticles2D
{
    //
}
PHP,
    'CanvasModulate' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class CanvasModulate extends Node2D
{
    public int $color = 0xFFFFFFFF;
}
PHP,
    'CanvasGroup' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class CanvasGroup extends Node2D
{
    public float $fitMargin = 0.0;
}
PHP,
    'BackBufferCopy' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class BackBufferCopy extends Node2D
{
    //
}
PHP,
    'Light2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

abstract class Light2D extends Node2D
{
    public bool $enabled = true;
    public int $color = 0xFFFFFFFF;
    public float $energy = 1.0;
}
PHP,
    'PointLight2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class PointLight2D extends Light2D
{
    public float $textureScale = 1.0;
}
PHP,
    'DirectionalLight2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class DirectionalLight2D extends Light2D
{
    public float $height = 0.0;
}
PHP,
    'LightOccluder2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\OccluderPolygon2D;

class LightOccluder2D extends Node2D
{
    protected ?OccluderPolygon2D $occluder = null;

    public function setOccluderPolygon(?OccluderPolygon2D $occluder): static
    {
        $this->occluder = $occluder;

        return $this;
    }
}
PHP,
    'Bone2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class Bone2D extends Node2D
{
    public float $restX = 0.0;
    public float $restY = 0.0;
}
PHP,
    'Skeleton2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class Skeleton2D extends Node2D
{
    /** @return list<Bone2D> */
    public function bones(): array
    {
        $bones = [];

        foreach ($this->children() as $child) {
            if ($child instanceof Bone2D) {
                $bones[] = $child;
            }
        }

        return $bones;
    }
}
PHP,
    'MeshInstance2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class MeshInstance2D extends Node2D
{
    /** @var list<array{0:float,1:float}> */
    public array $mesh = [];
}
PHP,
    'MultiMeshInstance2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class MultiMeshInstance2D extends Node2D
{
    public int $instanceCount = 0;
}
PHP,
    'TileMap' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\TileSet;

/** @deprecated Prefer TileMapLayer children in Godot 4.5 — kept for API parity. */
class TileMap extends Node2D
{
    protected ?TileSet $tileSet = null;

    public function setTileSet(?TileSet $tileSet): static
    {
        $this->tileSet = $tileSet;

        return $this;
    }

    public function tileSet(): ?TileSet
    {
        return $this->tileSet;
    }
}
PHP,
    'TileMapLayer' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\TileSet;
use ScrapyardIO\UX\Core\PaintContext;
use ScrapyardIO\UX\Geometry\Point;

class TileMapLayer extends Node2D
{
    protected ?TileSet $tileSet = null;
    /** @var array<string, int> */
    protected array $cells = [];

    public function setTileSet(?TileSet $tileSet): static
    {
        $this->tileSet = $tileSet;

        return $this;
    }

    public function setCell(int $x, int $y, int $sourceId = 0): static
    {
        $this->cells["{$x},{$y}"] = $sourceId;

        return $this;
    }

    public function getCell(int $x, int $y): int
    {
        return $this->cells["{$x},{$y}"] ?? -1;
    }

    protected function onDraw(PaintContext $ctx): void
    {
        if (is_null($this->tileSet)) {
            return;
        }

        $tw = $this->tileSet->tileWidth;
        $th = $this->tileSet->tileHeight;
        $local = $ctx->withOrigin(new Point((int) round($this->globalX()), (int) round($this->globalY())));

        foreach ($this->cells as $key => $sourceId) {
            [$cx, $cy] = array_map('intval', explode(',', $key));
            $local->fillRectLocal($cx * $tw, $cy * $th, $tw, $th, 0xFF888888);
        }

        unset($sourceId);
    }
}
PHP,
    'NavigationRegion2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Resources\NavigationPolygon;

class NavigationRegion2D extends Node2D
{
    protected ?NavigationPolygon $navigationPolygon = null;
    public bool $enabled = true;

    public function setNavigationPolygon(?NavigationPolygon $polygon): static
    {
        $this->navigationPolygon = $polygon;

        return $this;
    }
}
PHP,
    'NavigationLink2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class NavigationLink2D extends Node2D
{
    public float $startX = 0.0;
    public float $startY = 0.0;
    public float $endX = 0.0;
    public float $endY = 0.0;
    public bool $enabled = true;
}
PHP,
    'NavigationObstacle2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class NavigationObstacle2D extends Node2D
{
    /** @var list<array{0:float,1:float}> */
    public array $vertices = [];
    public bool $avoidanceEnabled = true;
}
PHP,
    'AudioStreamPlayer2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

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
PHP,
    'AudioListener2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class AudioListener2D extends Node2D
{
    public bool $current = true;
}
PHP,
    'Joint2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

abstract class Joint2D extends Node2D
{
    protected ?PhysicsBody2D $nodeA = null;
    protected ?PhysicsBody2D $nodeB = null;

    public function setNodeA(?PhysicsBody2D $body): static
    {
        $this->nodeA = $body;

        return $this;
    }

    public function setNodeB(?PhysicsBody2D $body): static
    {
        $this->nodeB = $body;

        return $this;
    }
}
PHP,
    'PinJoint2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class PinJoint2D extends Joint2D
{
    public float $softness = 0.0;
}
PHP,
    'GrooveJoint2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class GrooveJoint2D extends Joint2D
{
    public float $length = 50.0;
    public float $initialOffset = 25.0;
}
PHP,
    'DampedSpringJoint2D' => <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class DampedSpringJoint2D extends Joint2D
{
    public float $restLength = 0.0;
    public float $stiffness = 20.0;
    public float $damping = 1.0;
}
PHP,
];

foreach ($node2d as $name => $code) {
    writeIfMissing("{$root}/src/Nodes/Node2D/{$name}.php", $code);
}

// Resources helpers
writeIfMissing("{$root}/src/Resources/Curve2D.php", <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Resources;

class Curve2D
{
    /** @var list<array{0:float,1:float}> */
    protected array $points = [];

    public function addPoint(float $x, float $y): static
    {
        $this->points[] = [$x, $y];

        return $this;
    }

    /** @return array{0:float,1:float} */
    public function sample(float $ratio): array
    {
        if ($this->points === []) {
            return [0.0, 0.0];
        }

        $ratio = max(0.0, min(1.0, $ratio));
        $index = (int) floor($ratio * (count($this->points) - 1));

        return $this->points[$index];
    }

    public function pointCount(): int
    {
        return count($this->points);
    }
}
PHP);

writeIfMissing("{$root}/src/Resources/OccluderPolygon2D.php", <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Resources;

class OccluderPolygon2D
{
    /** @var list<array{0:float,1:float}> */
    public array $polygon = [];
    public bool $closed = true;
}
PHP);

writeIfMissing("{$root}/src/Resources/NavigationPolygon.php", <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Resources;

class NavigationPolygon
{
    /** @var list<array{0:float,1:float}> */
    public array $vertices = [];
    /** @var list<list<int>> */
    public array $polygons = [];
}
PHP);

writeIfMissing("{$root}/src/Resources/TileSet.php", <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Resources;

class TileSet
{
    public function __construct(
        public int $tileWidth = 16,
        public int $tileHeight = 16,
    ) {}
}
PHP);

writeIfMissing("{$root}/src/Resources/TileSetAtlasSource.php", <<<'PHP'
<?php

namespace ScrapyardIO\GameEngine\Resources;

class TileSetAtlasSource
{
    public function __construct(
        public ?Texture $texture = null,
        public int $textureRegionSizeX = 16,
        public int $textureRegionSizeY = 16,
    ) {}
}
PHP);

echo "Node2D catalog done\n";
