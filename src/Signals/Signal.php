<?php

namespace ScrapyardIO\GameEngine\Signals;

use Attribute;

/**
 * Declares a named signal and its argument signature for a node class.
 *
 * Example:
 *   #[Signal('pressed')]
 *   #[Signal('moved', ['x' => 'int', 'y' => 'int'])]
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class Signal
{
    /**
     * @param  array<string, string>  $args  Signal argument map: name => type
     */
    public function __construct(
        public string $name,
        public array $args = [],
    ) {}
}
