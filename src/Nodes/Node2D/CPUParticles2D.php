<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

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