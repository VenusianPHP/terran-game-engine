<?php
declare(strict_types=1);
namespace Terran\Input;

use Surface\Contracts\HumanInput\Events\GamepadConnected;
use Surface\Contracts\HumanInput\Events\GamepadDisconnected;
use Surface\Contracts\HumanInput\GamepadButton;
use Surface\Contracts\HumanInput\MouseButton;
use Surface\HumanInput\HumanInputManager;
use Terran\Input\Events\ActionPressed;
use Terran\Input\Events\ActionReleased;
use Terran\Input\Events\InputEvent;
use Terran\Input\Events\KeyPressed;
use Terran\Input\Events\KeyReleased;
use Terran\Input\Events\MouseButtonPressed;
use Terran\Input\Events\MouseButtonReleased;
use Terran\Input\Events\MouseMoved;
use Terran\Input\Events\MouseWheel;
use Terran\Input\Events\PadButtonPressed;
use Terran\Input\Events\PadButtonReleased;
use Terran\Input\Events\PadConnected;
use Terran\Input\Events\PadDisconnected;
use Terran\Input\Events\TextTyped;
use Terran\TerranException;
use Terran\World;

/**
 * The frame's only HumanInput reader. HumanInput ends a frame on reads (its
 * InputFrame), so one read per frame keeps every edge for the frame that
 * should see it. read() reads each device the game uses, once: those its
 * bindings name, plus terran.input.events while a live Scene overrides
 * input(). HumanInput's edge lists are already "since the last frame", so the
 * events come straight from them. Whatever HumanInput throws passes through.
 */
final class InputReader
{
    public const array EVENT_DEVICES = ['keyboard', 'mouse', 'pads'];

    /** @var array<string, string> pad id → the name it was last listed or connected under */
    private array $names = [];

    /** @param list<string> $events_devices */
    public function __construct(
        private readonly HumanInputManager $input,
        private readonly InputMap $map,
        private readonly array $events_devices = ['keyboard', 'mouse'],
        private readonly ?Seats $seats = null,
    ) {
        foreach ($events_devices as $device) {
            if (! in_array($device, self::EVENT_DEVICES, true)) {
                throw new TerranException("terran.input.events lists '{$device}'. It takes: ".implode(', ', self::EVENT_DEVICES).'.');
            }
        }
    }

    /** @return array{key: bool, mouse: bool, pad: bool} */
    public function devicesFor(World $world): array
    {
        $devices = $this->map->devices();
        if ($world->wantsInput()) {
            $devices['key'] = $devices['key'] || in_array('keyboard', $this->events_devices, true);
            $devices['mouse'] = $devices['mouse'] || in_array('mouse', $this->events_devices, true);
            $devices['pad'] = $devices['pad'] || in_array('pads', $this->events_devices, true);
        }

        return $devices;
    }

    /**
     * @param list<object> $mail the frame's loop mail
     * @return list<InputEvent>
     */
    public function read(array $mail, World $world): array
    {
        $devices = $this->devicesFor($world);
        $keyboard = $devices['key'] ? $this->input->keyboard() : null;
        $mouse = $devices['mouse'] ? $this->input->mouse() : null;
        $pads = $devices['pad'] ? array_values([...$this->input->gamePads(), ...$this->input->gameControllers()]) : [];
        if ($devices['pad']) {
            foreach ($pads as $pad) {
                $this->names[$pad->id()] = $pad->name();
            }
            // Seat this frame's listing first, so every event below carries the seat it has now.
            $this->seats?->reconcile($pads);
        }

        $events = [];
        foreach ($mail as $letter) {
            if ($letter instanceof GamepadConnected) {
                $this->names[$letter->id] = $letter->device_name;
                $events[] = new PadConnected($letter->id, $letter->device_name, $this->seats?->seatOf($letter->id));
            } elseif ($letter instanceof GamepadDisconnected) {
                $events[] = new PadDisconnected($letter->id, $this->names[$letter->id] ?? null, $this->seats?->seatOf($letter->id));
            }
        }

        if (! is_null($keyboard)) {
            $modifiers = $keyboard->modifiers();
            foreach ($keyboard->pressedKeys() as $key) {
                $events[] = new KeyPressed($key, $modifiers);
            }
            foreach ($keyboard->releasedKeys() as $key) {
                $events[] = new KeyReleased($key, $modifiers);
            }
            if (($text = $keyboard->text()) !== '') {
                $events[] = new TextTyped($text);
            }
        }

        if (! is_null($mouse)) {
            $viewport = $world->viewportForWindow($mouse->window());
            [$x, $y] = is_null($viewport) ? [$mouse->x(), $mouse->y()] : $viewport->toPixels($mouse->x(), $mouse->y());
            $name = $viewport?->name;
            foreach (MouseButton::cases() as $button) {
                if ($mouse->isPressed($button)) {
                    $events[] = new MouseButtonPressed($button, $name, $x, $y);
                }
                if ($mouse->wasReleased($button)) {
                    $events[] = new MouseButtonReleased($button, $name, $x, $y);
                }
            }
            ['dx' => $dx, 'dy' => $dy] = $mouse->motion();
            if ($dx != 0.0 || $dy != 0.0) {
                [$across, $down] = is_null($viewport) ? [1.0, 1.0] : $viewport->pixelsPerPoint();
                $events[] = new MouseMoved($name, $x, $y, $dx * $across, $dy * $down);
            }
            ['dx' => $wx, 'dy' => $wy] = $mouse->wheel();
            if ($wx != 0.0 || $wy != 0.0) {
                $events[] = new MouseWheel($name, $wx, $wy);
            }
        }

        foreach ($pads as $pad) {
            $seat = $this->seats?->seatOf($pad->id());
            foreach (GamepadButton::cases() as $button) {
                if (! $pad->supports($button)) {
                    continue;
                }
                if ($pad->isPressed($button)) {
                    $events[] = new PadButtonPressed($pad->id(), $button, $seat);
                }
                if ($pad->wasReleased($button)) {
                    $events[] = new PadButtonReleased($pad->id(), $button, $seat);
                }
            }
        }

        $this->map->capture($keyboard, $mouse, $pads);
        foreach ($this->map->edges() as ['action' => $action, 'seat' => $seat, 'kind' => $kind]) {
            $events[] = $kind === 'pressed' ? new ActionPressed($action, $seat) : new ActionReleased($action, $seat);
        }

        return $events;
    }
}
