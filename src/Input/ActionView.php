<?php
declare(strict_types=1);
namespace Terran\Input;

use Surface\Contracts\HumanInput\Devices\GameController;
use Surface\Contracts\HumanInput\Devices\GamePad;
use Surface\Contracts\HumanInput\Devices\Keyboard;
use Surface\Contracts\HumanInput\Devices\Mouse;
use Terran\TerranException;

/**
 * Action state over one set of sources: every source (InputMap's own view),
 * one seat's pad and the keyboard on the keyboard seat, or the input no seat
 * owns. Button edges come from HumanInput's own edges, so a tap inside one
 * frame reads pressed and released; an axis binding presses past its dead
 * zone and releases when it falls back. An action presses only when it was
 * not held, and releases when nothing holds it any more, which includes its
 * pad leaving.
 */
final class ActionView implements Actions
{
    /** @var array<string, array{down: bool, pressed: bool, released: bool, strength: float}> */
    private array $state = [];

    /** @var array<string, array<int, bool>> action → binding index → axis past its dead zone last frame */
    private array $axis_was_active = [];

    /** @param array<string, list<Binding>> $bindings */
    public function __construct(private readonly array $bindings)
    {
        foreach (array_keys($bindings) as $action) {
            $this->state[$action] = ['down' => false, 'pressed' => false, 'released' => false, 'strength' => 0.0];
        }
    }

    /**
     * @param list<GamePad> $pads
     * @return array{pressed: list<string>, released: list<string>}
     */
    public function capture(?Keyboard $keyboard, ?Mouse $mouse, array $pads): array
    {
        $pressed = [];
        $released = [];
        foreach ($this->bindings as $action => $bindings) {
            $down = false;
            $edge_down = false;
            $edge_up = false;
            $strength = 0.0;
            foreach ($bindings as $i => $binding) {
                [$held, $went_down, $went_up, $amount] = $this->read($action, $i, $binding, $keyboard, $mouse, $pads);
                $down = $down || $held;
                $edge_down = $edge_down || $went_down;
                $edge_up = $edge_up || $went_up;
                $strength = max($strength, $amount);
            }
            $was_down = $this->state[$action]['down'];
            $is_pressed = $edge_down && ! $was_down;
            $is_released = ! $down && ($edge_up || $was_down);
            $this->state[$action] = ['down' => $down, 'pressed' => $is_pressed, 'released' => $is_released, 'strength' => $strength];
            if ($is_pressed) {
                $pressed[] = $action;
            }
            if ($is_released) {
                $released[] = $action;
            }
        }

        return ['pressed' => $pressed, 'released' => $released];
    }

    public function isDown(string $action): bool { return $this->stateOf($action)['down']; }

    public function isPressed(string $action): bool { return $this->stateOf($action)['pressed']; }

    public function wasReleased(string $action): bool { return $this->stateOf($action)['released']; }

    public function strength(string $action): float { return $this->stateOf($action)['strength']; }

    public function axis(string $negative, string $positive): float
    {
        return $this->strength($positive) - $this->strength($negative);
    }

    /** @return array{down: bool, pressed: bool, released: bool, strength: float} */
    private function stateOf(string $action): array
    {
        return $this->state[$action] ?? throw new TerranException("No action '{$action}'. Actions: ".implode(', ', array_keys($this->bindings)).'.');
    }

    /**
     * @param list<GamePad> $pads
     * @return array{0: bool, 1: bool, 2: bool, 3: float} held, went down, went up, strength
     */
    private function read(string $action, int $index, Binding $binding, ?Keyboard $keyboard, ?Mouse $mouse, array $pads): array
    {
        switch ($binding->device) {
            case BindingDevice::KEY:
                if (is_null($keyboard)) {
                    return [false, false, false, 0.0];
                }
                $held = $keyboard->isDown($binding->input);

                return [$held, $keyboard->isPressed($binding->input), $keyboard->wasReleased($binding->input), $held ? 1.0 : 0.0];
            case BindingDevice::MOUSE:
                if (is_null($mouse)) {
                    return [false, false, false, 0.0];
                }
                $held = $mouse->isDown($binding->input);

                return [$held, $mouse->isPressed($binding->input), $mouse->wasReleased($binding->input), $held ? 1.0 : 0.0];
            case BindingDevice::PAD:
                $held = $went_down = $went_up = false;
                foreach ($pads as $pad) {
                    if (! $pad->supports($binding->input)) {
                        continue;
                    }
                    $held = $held || $pad->isDown($binding->input);
                    $went_down = $went_down || $pad->isPressed($binding->input);
                    $went_up = $went_up || $pad->wasReleased($binding->input);
                }

                return [$held, $went_down, $went_up, $held ? 1.0 : 0.0];
            case BindingDevice::AXIS:
                $strength = 0.0;
                foreach ($pads as $pad) {
                    if (! $pad instanceof GameController || ! in_array($binding->input, $pad->axes(), true)) {
                        continue;
                    }
                    $value = $pad->axis($binding->input) * $binding->sign;
                    if ($value > $binding->dead_zone) {
                        $strength = max($strength, min(1.0, ($value - $binding->dead_zone) / (1 - $binding->dead_zone)));
                    }
                }
                $active = $strength > 0.0;
                $was = $this->axis_was_active[$action][$index] ?? false;
                $this->axis_was_active[$action][$index] = $active;

                return [$active, $active && ! $was, ! $active && $was, $strength];
        }
    }
}
