<?php
declare(strict_types=1);
namespace Terran\Input\Events;

use Surface\Contracts\HumanInput\Key;
use Surface\Contracts\HumanInput\Modifiers;

final readonly class KeyPressed extends InputEvent
{
    public function __construct(public Key $key, public Modifiers $modifiers) {}
    public function name(): string { return 'terran.input.key.pressed'; }
}
