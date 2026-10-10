<?php
declare(strict_types=1);
namespace Terran\Autoload;

use Attribute;

/** A Scene class needs this service (or Scene). One instance per World, shared by every Scene that declares it; a Scene class is added under the root as $as. */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Autoload
{
    /** @param class-string $class */
    public function __construct(public readonly string $class, public readonly ?string $as = null) {}
}
