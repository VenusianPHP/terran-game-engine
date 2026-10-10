<?php
declare(strict_types=1);
namespace Terran\Input;

use Surface\Contracts\HumanInput\GamepadAxis;
use Surface\Contracts\HumanInput\GamepadButton;
use Surface\Contracts\HumanInput\Key;
use Surface\Contracts\HumanInput\MouseButton;
use Terran\TerranException;

/** One input an action listens to: key:<Key>, mouse:<MouseButton>, pad:<GamepadButton>, axis:<GamepadAxis>:+|-[:dead zone]. */
final readonly class Binding
{
    public const float DEFAULT_DEAD_ZONE = 0.15;

    public function __construct(
        public BindingDevice $device,
        public Key|MouseButton|GamepadButton|GamepadAxis $input,
        public int $sign = 1,
        public float $dead_zone = self::DEFAULT_DEAD_ZONE,
    ) {}

    public static function parse(string $action, string $spec): self
    {
        $parts = explode(':', $spec);
        $device = BindingDevice::tryFrom($parts[0]);
        $arity = match ($device) {
            BindingDevice::AXIS => [2, 4],
            null => [0, -1],
            default => [2, 2],
        };
        if (is_null($device) || count($parts) < $arity[0] || count($parts) > $arity[1]) {
            throw new TerranException("Action '{$action}' binding '{$spec}' is not key:<key>, mouse:<button>, pad:<button> or axis:<axis>:+|-[:dead zone].");
        }

        [$enum, $label] = match ($device) {
            BindingDevice::KEY => [Key::class, 'Key'],
            BindingDevice::MOUSE => [MouseButton::class, 'MouseButton'],
            BindingDevice::PAD => [GamepadButton::class, 'GamepadButton'],
            BindingDevice::AXIS => [GamepadAxis::class, 'GamepadAxis'],
        };
        $input = $enum::tryFrom($parts[1]) ?? throw new TerranException(
            "Action '{$action}' binding '{$spec}': '{$parts[1]}' is not a {$label}. {$label}s: ".implode(', ', array_map(fn ($case) => $case->value, $enum::cases())).'.'
        );

        if ($device !== BindingDevice::AXIS) {
            return new self($device, $input);
        }

        $sign = match ($parts[2] ?? null) {
            '+' => 1,
            '-' => -1,
            default => throw new TerranException("Action '{$action}' binding '{$spec}' needs a direction: axis:{$parts[1]}:+ or axis:{$parts[1]}:-."),
        };
        $dead_zone = self::DEFAULT_DEAD_ZONE;
        if (isset($parts[3])) {
            if (! is_numeric($parts[3]) || (float) $parts[3] < 0 || (float) $parts[3] >= 1) {
                throw new TerranException("Action '{$action}' binding '{$spec}': the dead zone is at least 0 and below 1, got {$parts[3]}.");
            }
            $dead_zone = (float) $parts[3];
        }

        return new self($device, $input, $sign, $dead_zone);
    }
}
