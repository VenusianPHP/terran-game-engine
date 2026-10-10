<?php
declare(strict_types=1);
namespace Terran\Input;

use Surface\Contracts\HumanInput\Devices\GamePad;
use Terran\TerranException;

/**
 * Player seats, which HumanInput leaves to the game. reconcile() takes each
 * frame's pad listing, so seating follows what HumanInput lists. A pad is
 * known by hardwareId() ?? id(): the same physical pad keeps its seat across
 * a reconnect, whatever id it comes back under. A pad that leaves keeps its
 * seat reserved; a new pad takes the lowest seat neither held nor reserved,
 * else takes over the lowest reservation, else stays unseated. A pad with no
 * hardware id (a source with no identity for it) is known by its id, which a
 * reconnect changes; it first takes back a seat reserved for an absent pad
 * that also had none and the same name, since two such pads cannot be told
 * apart anyway. Every seat a
 * pad takes lights it with setPlayerIndex($seat - 1); a pad that loses its
 * seat while present is turned off with setPlayerIndex(null).
 */
final class Seats
{
    /** @var array<int, string> seat → key held or reserved */
    private array $seats = [];

    /** @var array<string, string> pad id → key, for every pad id ever listed */
    private array $key_of = [];

    /** @var array<string, GamePad> key → the pad listed under it now */
    private array $present = [];

    /** @var array<string, string> key → name, for pads listed with no hardware id */
    private array $nameless_id = [];

    public function __construct(
        private readonly int $max = 4,
        private readonly ?int $keyboard_seat = 1,
        private readonly bool $seat_on_connect = true,
    ) {
        if ($max < 0) {
            throw new TerranException("terran.input.seats is 0 or more; got {$max}.");
        }
        if ($max > 0 && ! is_null($keyboard_seat) && ($keyboard_seat < 1 || $keyboard_seat > $max)) {
            throw new TerranException("terran.input.keyboard_seat is null or a seat from 1 to {$max}; got {$keyboard_seat}.");
        }
    }

    public function max(): int { return $this->max; }

    /** The seat keyboard and mouse belong to; null when seating is off or they belong to none. */
    public function keyboardSeat(): ?int { return $this->max === 0 ? null : $this->keyboard_seat; }

    /**
     * @param list<GamePad> $pads every pad and controller HumanInput lists this frame
     * @return array{seated: array<string, int>, left: array<string, int>} pads that took a seat this frame (listing order), and seated pads that left
     */
    public function reconcile(array $pads): array
    {
        $listed = [];
        foreach ($pads as $pad) {
            $key = $pad->hardwareId() ?? $pad->id();
            $this->key_of[$pad->id()] = $key;
            if (is_null($pad->hardwareId())) {
                $this->nameless_id[$key] = $pad->name();
            }
            $listed[$key] = $pad;
        }

        $left = [];
        foreach ($this->present as $key => $pad) {
            if (! isset($listed[$key]) && ! is_null($seat = $this->seatOfKey($key))) {
                $left[$pad->id()] = $seat;
            }
        }

        $was = $this->present;
        $this->present = $listed;
        if ($this->max === 0) {
            return ['seated' => [], 'left' => []];
        }

        $seated = [];
        foreach ($listed as $key => $pad) {
            $seat = $this->seatOfKey($key);
            // Back after a frame away, or replugged between two listings under a new id: light the pad again.
            if (! is_null($seat) && (! isset($was[$key]) || $was[$key]->id() !== $pad->id())) {
                $pad->setPlayerIndex($seat - 1);
                $seated[$pad->id()] = $seat;
            }
        }
        if ($this->seat_on_connect) {
            foreach ($listed as $key => $pad) {
                if (is_null($this->seatOfKey($key)) && is_null($pad->hardwareId()) && ! is_null($seat = $this->namesakeSeat($pad->name()))) {
                    $this->seats[$seat] = $key;
                    $pad->setPlayerIndex($seat - 1);
                    $seated[$pad->id()] = $seat;
                }
            }
            foreach ($listed as $key => $pad) {
                if (is_null($this->seatOfKey($key)) && ! is_null($seat = $this->openSeat())) {
                    $this->seats[$seat] = $key;
                    $pad->setPlayerIndex($seat - 1);
                    $seated[$pad->id()] = $seat;
                }
            }
        }

        $ordered = [];
        foreach ($listed as $pad) {
            if (isset($seated[$pad->id()])) {
                $ordered[$pad->id()] = $seated[$pad->id()];
            }
        }

        return ['seated' => $ordered, 'left' => $left];
    }

    /** The seat a pad holds or has reserved, by any id it was listed under. */
    public function seatOf(string $pad_id): ?int
    {
        $key = $this->key_of[$pad_id] ?? null;

        return is_null($key) ? null : $this->seatOfKey($key);
    }

    /** The id of the pad in $seat now; null when the seat is empty or its pad is gone. */
    public function padIn(int $seat): ?string
    {
        return $this->pad($seat)?->id();
    }

    public function pad(int $seat): ?GamePad
    {
        $this->check($seat);
        $key = $this->seats[$seat] ?? null;

        return is_null($key) ? null : ($this->present[$key] ?? null);
    }

    /** @return array<int, string> seat → id of every seated pad present now, by seat */
    public function all(): array
    {
        $out = [];
        ksort($this->seats);
        foreach ($this->seats as $seat => $key) {
            if (isset($this->present[$key])) {
                $out[$seat] = $this->present[$key]->id();
            }
        }

        return $out;
    }

    /** A game's own seating (a lobby): $pad_id leaves any seat it held and takes $seat from whatever held or reserved it. */
    public function assign(int $seat, string $pad_id): void
    {
        $this->check($seat);
        $pad = null;
        foreach ($this->present as $listed) {
            if ($listed->id() === $pad_id) {
                $pad = $listed;
            }
        }
        if (is_null($pad)) {
            $ids = array_map(fn (GamePad $p): string => $p->id(), array_values($this->present));
            throw new TerranException("Pad '{$pad_id}' is not connected. Connected: ".($ids === [] ? 'none' : implode(', ', $ids)).'.');
        }

        $key = $this->key_of[$pad_id];
        $holder = $this->seats[$seat] ?? null;
        if ($holder === $key) {
            return;
        }
        if (! is_null($holder) && isset($this->present[$holder])) {
            $this->present[$holder]->setPlayerIndex(null);
        }
        if (! is_null($old = $this->seatOfKey($key))) {
            unset($this->seats[$old]);
        }
        $this->seats[$seat] = $key;
        $pad->setPlayerIndex($seat - 1);
    }

    /** Free $seat and its reservation; a pad present in it goes dark. */
    public function release(int $seat): void
    {
        $this->check($seat);
        $key = $this->seats[$seat] ?? null;
        if (is_null($key)) {
            return;
        }
        if (isset($this->present[$key])) {
            $this->present[$key]->setPlayerIndex(null);
        }
        unset($this->seats[$seat]);
    }

    /** Seat numbers outside 1..max throw, naming the range; any seat throws while seating is off. */
    public function check(int $seat): void
    {
        if ($this->max === 0) {
            throw new TerranException('Seating is off: terran.input.seats is 0.');
        }
        if ($seat < 1) {
            throw new TerranException("Seats are numbered from 1; got {$seat}.");
        }
        if ($seat > $this->max) {
            throw new TerranException("Seat {$seat} is beyond terran.input.seats ({$this->max}).");
        }
    }

    private function seatOfKey(string $key): ?int
    {
        $seat = array_search($key, $this->seats, true);

        return $seat === false ? null : $seat;
    }

    /** The lowest seat reserved for an absent pad that had no hardware id and was listed as $name. */
    private function namesakeSeat(string $name): ?int
    {
        ksort($this->seats);
        foreach ($this->seats as $seat => $key) {
            if (! isset($this->present[$key]) && ($this->nameless_id[$key] ?? null) === $name) {
                return $seat;
            }
        }

        return null;
    }

    /** The lowest seat neither held nor reserved; else the lowest reserved for a pad that is gone (its reservation ends); else none. */
    private function openSeat(): ?int
    {
        for ($seat = 1; $seat <= $this->max; $seat++) {
            if (! isset($this->seats[$seat])) {
                return $seat;
            }
        }
        for ($seat = 1; $seat <= $this->max; $seat++) {
            if (! isset($this->present[$this->seats[$seat]])) {
                return $seat;
            }
        }

        return null;
    }
}
