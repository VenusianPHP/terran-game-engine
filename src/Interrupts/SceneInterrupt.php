<?php
declare(strict_types=1);
namespace Terran\Interrupts;

use Terran\Scene;
use Voyager\Contracts\Signals\NamedSignal;

/** An interrupt a live Scene emitted, as the rest of the application hears it on app('signals'). */
final readonly class SceneInterrupt implements NamedSignal
{
    public string $path;

    /** @param list<mixed> $payload */
    public function __construct(public Scene $scene, public string $interrupt, public array $payload)
    {
        $this->path = $scene->path();
    }

    public function name(): string
    {
        return "terran.{$this->path}.{$this->interrupt}";
    }
}
