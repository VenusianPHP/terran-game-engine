<?php
declare(strict_types=1);
namespace Terran\Interrupts;

use Attribute;

/** A Scene class emits this interrupt, carrying values of these types (class names or int, float, string, bool, array, object, callable, mixed; ?T allows null). */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Interrupt
{
    /** @param list<string> $payload */
    public function __construct(public readonly string $name, public readonly array $payload = []) {}
}
