<?php

namespace ScrapyardIO\GameEngine\Signals;

/**
 * Value object describing one signal subscription.
 */
class SignalConnection
{
    /**
     * @param  object  $target  Usually a Node, but any object is allowed.
     * @param  callable|string  $handler
     */
    public function __construct(
        public string $signal,
        public object $target,
        public mixed $handler,
        public bool $oneshot = false,
    ) {}
}
