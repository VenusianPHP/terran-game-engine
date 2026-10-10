<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

/** Stacks its children top to bottom. */
class VBox extends BoxContainer
{
    protected function vertical(): bool { return true; }
}
