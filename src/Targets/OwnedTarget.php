<?php
declare(strict_types=1);
namespace Terran\Targets;

use Closure;
use Surface\Contracts\Drawing\OutputTarget;

/** A target the opener produced: what to draw on, which engine config asked for, whether it is still there, and how to let it go. */
final class OwnedTarget
{
    private bool $closed = false;

    public function __construct(
        public readonly string $name,
        public readonly OutputTarget $target,
        public readonly ?string $engine,
        /** The staged window Terran opened under this name, or null when the target is not Terran's window. */
        public readonly ?string $window,
        private readonly ?Closure $close,
        /** Whether the target can still be drawn on; null when it lives as long as the engine does. */
        private readonly ?Closure $alive = null,
    ) {}

    public function alive(): bool
    {
        return ! $this->closed && (is_null($this->alive) || ($this->alive)());
    }

    public function close(): void
    {
        if ($this->closed || is_null($this->close)) {
            return;
        }
        $this->closed = true;
        ($this->close)();
    }
}
