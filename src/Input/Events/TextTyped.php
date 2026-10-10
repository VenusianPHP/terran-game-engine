<?php
declare(strict_types=1);
namespace Terran\Input\Events;

final readonly class TextTyped extends InputEvent
{
    public function __construct(public string $text) {}
    public function name(): string { return 'terran.input.text'; }
}
