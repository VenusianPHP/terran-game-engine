<?php

namespace ScrapyardIO\GameEngine\Input;

use ScrapyardIO\GameEngine\Game;
use ScrapyardIO\GameEngine\Input\Events\InputEventAction;
use ScrapyardIO\GameEngine\Input\Events\InputEventKey;
use ScrapyardIO\GameEngine\Input\Events\InputEventMouseButton;
use ScrapyardIO\GameEngine\SceneTree;
use ScrapyardIO\Tubes\HumanInput\EngineInput;
use ScrapyardIO\Tubes\HumanInput\Enums\MouseButton;
use ScrapyardIO\Tubes\Inputs\InputHandler;

/**
 * Pumps tubes {@see InputHandler} / {@see EngineInput} into Godot-like events.
 *
 * Any *-gfx companion that registers an InputHandler works — no brand imports.
 */
class InputServer
{
    protected ?EngineInput $engineInput = null;

    protected InputMap $map;

    /**
     * @var array<string, bool>
     */
    protected array $keyState = [];

    /**
     * @var array<string, bool>
     */
    protected array $mouseState = [];

    /**
     * @var array<string, bool>
     */
    protected array $actionPressed = [];

    public function __construct(?InputMap $map = null)
    {
        $this->map = $map ?? new InputMap;
    }

    public function map(): InputMap
    {
        return $this->map;
    }

    public function bindEngineInput(EngineInput $input): static
    {
        $this->engineInput = $input;

        return $this;
    }

    public function bindHandler(InputHandler $handler): static
    {
        $this->engineInput = new EngineInput($handler);

        return $this;
    }

    public function engineInput(): ?EngineInput
    {
        return $this->engineInput;
    }

    public function isActionPressed(string $action): bool
    {
        return $this->actionPressed[$action] ?? false;
    }

    public function pump(SceneTree $tree, ?Game $game = null): void
    {
        if (is_null($this->engineInput)) {
            return;
        }

        if (! is_null($game) && ! $this->canvasHasInputFocus($game)) {
            $this->clearInputState($tree);

            return;
        }

        $this->engineInput->poll();

        $keyboard = $this->engineInput->keyboard();
        $mouse = $this->engineInput->mouse();

        if (! is_null($keyboard)) {
            $pressed = [];

            foreach ($keyboard->keys() as $key => $down) {
                if ($down === true) {
                    $pressed[] = (string) $key;
                }
            }

            $this->diffKeys($tree, $pressed);
        }

        if (! is_null($mouse)) {
            foreach (MouseButton::cases() as $button) {
                $down = $mouse->isPressed($button);
                $code = $button->value;
                $was = $this->mouseState[$code] ?? false;

                if ($down !== $was) {
                    $this->mouseState[$code] = $down;
                    $x = method_exists($mouse, 'x') ? (int) $mouse->x() : 0;
                    $y = method_exists($mouse, 'y') ? (int) $mouse->y() : 0;
                    $tree->dispatchInput(new InputEventMouseButton($code, $down, $x, $y));

                    foreach ($this->map->actionsForMouseButton($code) as $action) {
                        $this->actionPressed[$action] = $down;
                        $tree->dispatchInput(new InputEventAction($action, $down));
                    }
                }
            }
        }
    }

    protected function canvasHasInputFocus(Game $game): bool
    {
        $canvas = $game->canvas();

        if ($canvas instanceof \ScrapyardIO\Tubes\Canvas\OSWindow) {
            return $canvas->hasInputFocus();
        }

        return true;
    }

    /**
     * Release held keys/actions when the window blurs so edges don't stick.
     */
    protected function clearInputState(SceneTree $tree): void
    {
        foreach ($this->keyState as $key => $wasDown) {
            if (! $wasDown) {
                continue;
            }

            $this->keyState[$key] = false;
            $tree->dispatchInput(new InputEventKey($key, false));

            foreach ($this->map->actionsForKey($key) as $action) {
                $this->actionPressed[$action] = false;
                $tree->dispatchInput(new InputEventAction($action, false));
            }
        }

        foreach ($this->mouseState as $code => $wasDown) {
            if (! $wasDown) {
                continue;
            }

            $this->mouseState[$code] = false;
            $tree->dispatchInput(new InputEventMouseButton($code, false, 0, 0));

            foreach ($this->map->actionsForMouseButton($code) as $action) {
                $this->actionPressed[$action] = false;
                $tree->dispatchInput(new InputEventAction($action, false));
            }
        }
    }

    /**
     * @param  list<string>  $pressedNow
     */
    protected function diffKeys(SceneTree $tree, array $pressedNow): void
    {
        $now = array_fill_keys($pressedNow, true);

        foreach ($now as $key => $_) {
            if (! ($this->keyState[$key] ?? false)) {
                $this->keyState[$key] = true;
                $tree->dispatchInput(new InputEventKey($key, true));

                foreach ($this->map->actionsForKey($key) as $action) {
                    $this->actionPressed[$action] = true;
                    $tree->dispatchInput(new InputEventAction($action, true));
                }
            }
        }

        foreach ($this->keyState as $key => $wasDown) {
            if ($wasDown && ! isset($now[$key])) {
                $this->keyState[$key] = false;
                $tree->dispatchInput(new InputEventKey($key, false));

                foreach ($this->map->actionsForKey($key) as $action) {
                    $this->actionPressed[$action] = false;
                    $tree->dispatchInput(new InputEventAction($action, false));
                }
            }
        }
    }

    public function inject(SceneTree $tree, InputEventKey|InputEventMouseButton|InputEventAction $event): void
    {
        if ($event instanceof InputEventKey) {
            $this->keyState[$event->key] = $event->pressed;

            foreach ($this->map->actionsForKey($event->key) as $action) {
                $this->actionPressed[$action] = $event->pressed;
                $tree->dispatchInput(new InputEventAction($action, $event->pressed));
            }
        }

        $tree->dispatchInput($event);
    }
}
