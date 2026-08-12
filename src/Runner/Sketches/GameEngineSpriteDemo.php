<?php

namespace ScrapyardIO\GameEngine\Runner\Sketches;

use Fabricate\Contracts\Sketches\Attributes\Sketch as SketchAttribute;
use Fabricate\Contracts\Sketches\SketchLoopResult;
use Fabricate\Sketches\Sketch;
use ScrapyardIO\GameEngine\Game;
use ScrapyardIO\GameEngine\Nodes\Node;
use ScrapyardIO\GameEngine\Nodes\Node2D\CharacterBody2D;
use ScrapyardIO\GameEngine\Nodes\Node2D\PhysicsBounds2D;
use ScrapyardIO\GameEngine\Nodes\Node2D\Sprite2D;
use ScrapyardIO\GameEngine\Resources\ImageTexture;
use ScrapyardIO\Tubes\Rendering\Renderer2D;
use ScrapyardIO\Tubes\Windows\WindowManager;
use ScrapyardIO\UX\Components\Text\Label;
use ScrapyardIO\UX\Support\Color;

/**
 * Smoke demo: OSWindow + Sprite2D + CharacterBody2D + UX Label HUD.
 *
 *   ./runner game-engine-sprite-demo
 */
#[SketchAttribute('game-engine-sprite-demo')]
class GameEngineSpriteDemo extends Sketch
{
    protected string $description = 'Game engine demo — Sprite2D + CharacterBody2D on tubes OSWindow profile';

    protected ?Game $game = null;

    protected int $ticks = 0;

    public function boot(): void
    {
        $profile = (string) config('tubes.defaults.canvas', 'metal');
        $window = app(WindowManager::class)->profile($profile)->open();
        $renderer = app(Renderer2D::class);

        $root = new Node('root');
        $bounds = new PhysicsBounds2D('world');
        $bounds->width = $window->width();
        $bounds->height = $window->height();

        $sprite = (new Sprite2D('hero'))
            ->setTexture(ImageTexture::solid(32, 32, 0xFFFF8800))
            ->setPosition(64, 64);

        $body = new CharacterBody2D('player');
        $body->id = 1;
        $body->width = 32;
        $body->height = 32;
        $body->setPosition(120, 120);
        $body->setVelocity(80, 40);

        $hud = Label::of('game-engine 0.7', Color::fromHex('#FFFFFF'));

        $bounds->addChild($sprite);
        $bounds->addChild($body);
        $root->addChild($bounds);
        $root->addChild($hud);

        $this->game = new Game($window, $renderer);
        $this->game->input()->map()->actionAddKey('ui_right', 'ArrowRight');
        $this->game->setRoot($root);
    }

    public function loop(): SketchLoopResult
    {
        if (is_null($this->game)) {
            return SketchLoopResult::STOP;
        }

        $this->game->tick(1 / 60);
        $this->ticks++;

        if ($this->ticks >= 180) {
            $this->game->stop();

            return SketchLoopResult::STOP;
        }

        return SketchLoopResult::CONTINUE;
    }

    public function shutdown(): void
    {
        $this->game?->canvas()->close();
        $this->game = null;
    }
}
