<?php
declare(strict_types=1);
namespace Terran\Input;

use Surface\Contracts\HumanInput\Devices\GamePad;
use Surface\Contracts\HumanInput\Devices\Keyboard;
use Surface\Contracts\HumanInput\Devices\Mouse;
use Terran\TerranException;

/**
 * Godot's InputMap: named actions over bindings. capture() is called once per
 * frame by InputReader with the devices it read (null for one it did not) and
 * the frame's pad listing. The map itself reads every source (the "any" view);
 * with Seats, each seat keeps its own view over its pad, plus the keyboard and
 * mouse on the keyboard seat, and the input no seat owns keeps one more. Every
 * press lands in exactly one of those, which edges() reports for the events.
 */
final class InputMap implements Actions
{
    /** @var array<string, list<Binding>> */
    private array $bindings = [];

    private ActionView $any;

    private ActionView $unseated;

    /** @var array<int, ActionView> */
    private array $by_seat = [];

    /** @var list<array{action: string, seat: ?int, kind: 'pressed'|'released'}> */
    private array $edges = [];

    private PhysicsLatch $latch;

    /** @param array<string, list<string>> $actions terran.input.actions */
    public function __construct(array $actions, private readonly ?Seats $seats = null)
    {
        foreach ($actions as $action => $specs) {
            $action = (string) $action;
            $this->bindings[$action] = array_map(fn ($spec): Binding => Binding::parse($action, (string) $spec), array_values((array) $specs));
        }
        $this->any = new ActionView($this->bindings);
        $this->unseated = new ActionView($this->bindings);
        for ($seat = 1; $seat <= $this->seatCount(); $seat++) {
            $this->by_seat[$seat] = new ActionView($this->bindings);
        }
        $this->latch = new PhysicsLatch($this);
    }

    /** @return list<string> */
    public function actions(): array { return array_keys($this->bindings); }

    /** @return array{key: bool, mouse: bool, pad: bool} */
    public function devices(): array
    {
        $out = ['key' => false, 'mouse' => false, 'pad' => false];
        foreach ($this->bindings as $bindings) {
            foreach ($bindings as $binding) {
                match ($binding->device) {
                    BindingDevice::KEY => $out['key'] = true,
                    BindingDevice::MOUSE => $out['mouse'] = true,
                    BindingDevice::PAD, BindingDevice::AXIS => $out['pad'] = true,
                };
            }
        }

        return $out;
    }

    /**
     * @param list<GamePad> $pads every pad and controller read this frame, after Seats reconciled them
     * @return array{pressed: list<string>, released: list<string>} the "any" view's edges
     */
    public function capture(?Keyboard $keyboard, ?Mouse $mouse, array $pads): array
    {
        $any = $this->any->capture($keyboard, $mouse, $pads);
        $this->latch->latch('any', $any['pressed'], $any['released']);

        if ($this->seatCount() === 0) {
            $this->edges = self::edgesOf([[null, $any]]);

            return $any;
        }

        $keyboard_seat = $this->seats->keyboardSeat();
        $scoped = [];
        foreach ($this->by_seat as $seat => $view) {
            $pad = $this->seats->pad($seat);
            $own = $seat === $keyboard_seat;
            $scoped[] = [$seat, $result = $view->capture($own ? $keyboard : null, $own ? $mouse : null, is_null($pad) ? [] : [$pad])];
            $this->latch->latch($seat, $result['pressed'], $result['released']);
        }
        $loose = array_values(array_filter($pads, fn (GamePad $pad): bool => is_null($this->seats->seatOf($pad->id()))));
        $none = is_null($keyboard_seat);
        $scoped[] = [null, $this->unseated->capture($none ? $keyboard : null, $none ? $mouse : null, $loose)];
        $this->edges = self::edgesOf($scoped);

        return $any;
    }

    /**
     * The last capture's edges, each once: by seat in seat order, then input no seat owns (seat null); presses before releases.
     *
     * @return list<array{action: string, seat: ?int, kind: 'pressed'|'released'}>
     */
    public function edges(): array { return $this->edges; }

    /** One seat's actions: its pad, plus the keyboard and mouse on the keyboard seat. */
    public function seat(int $seat): Actions
    {
        $this->checkSeat($seat);

        return $this->by_seat[$seat];
    }

    public function checkSeat(int $seat): void
    {
        if ($this->seatCount() === 0) {
            throw new TerranException('Seating is off: terran.input.seats is 0.');
        }
        $this->seats->check($seat);
    }

    public function physics(): PhysicsLatch { return $this->latch; }

    public function isDown(string $action): bool { return $this->any->isDown($action); }

    public function isPressed(string $action): bool { return $this->any->isPressed($action); }

    public function wasReleased(string $action): bool { return $this->any->wasReleased($action); }

    public function strength(string $action): float { return $this->any->strength($action); }

    public function axis(string $negative, string $positive): float { return $this->any->axis($negative, $positive); }

    private function seatCount(): int { return $this->seats?->max() ?? 0; }

    /**
     * @param list<array{0: ?int, 1: array{pressed: list<string>, released: list<string>}}> $scoped
     * @return list<array{action: string, seat: ?int, kind: 'pressed'|'released'}>
     */
    private static function edgesOf(array $scoped): array
    {
        $out = [];
        foreach (['pressed', 'released'] as $kind) {
            foreach ($scoped as [$seat, $result]) {
                foreach ($result[$kind] as $action) {
                    $out[] = ['action' => $action, 'seat' => $seat, 'kind' => $kind];
                }
            }
        }

        return $out;
    }
}
