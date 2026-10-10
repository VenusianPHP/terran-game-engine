<?php
declare(strict_types=1);
namespace Tests\Fixtures;

use Surface\Contracts\HumanInput\Devices\Keyboard as KeyboardContract;
use Surface\Contracts\HumanInput\Devices\Mouse as MouseContract;
use Surface\HumanInput\Devices\GameController;
use Surface\HumanInput\Devices\GamePad;
use Surface\HumanInput\Devices\Keyboard;
use Surface\HumanInput\Devices\Mouse;
use Surface\HumanInput\HumanInputManager;
use Surface\HumanInput\InputFrame;
use Throwable;

/**
 * HumanInput with Surface's own devices driven by hand. Counts each device
 * read; a device can be set to throw, as a connected session with no input
 * engine makes keyboard() and mouse() do.
 */
final class FakeHumanInput extends HumanInputManager
{
    public Keyboard $kb;
    public Mouse $pointer;
    /** @var array<string, GamePad> */
    public array $hand_pads = [];
    /** @var array<string, GameController> */
    public array $controllers = [];
    /** @var array<string, int> */
    public array $reads = ['keyboard' => 0, 'mouse' => 0, 'pads' => 0, 'controllers' => 0];
    public ?Throwable $keyboard_fails = null;

    public function __construct(public readonly InputFrame $input_frame)
    {
        $this->kb = new Keyboard($input_frame);
        $this->pointer = new Mouse($input_frame);
    }

    public function keyboard(): KeyboardContract
    {
        $this->reads['keyboard']++;
        if (! is_null($this->keyboard_fails)) {
            throw $this->keyboard_fails;
        }

        return $this->kb;
    }

    public function mouse(): MouseContract
    {
        $this->reads['mouse']++;

        return $this->pointer;
    }

    public function gamePads(): array
    {
        $this->reads['pads']++;

        return $this->hand_pads;
    }

    public function gameControllers(): array
    {
        $this->reads['controllers']++;

        return $this->controllers;
    }

    /** What HumanInput's next poll does after a frame read: clear every edge. */
    public function settle(): void
    {
        $this->kb->settle();
        $this->pointer->settle();
        foreach ([...$this->hand_pads, ...$this->controllers] as $pad) {
            $pad->settle();
        }
    }
}
