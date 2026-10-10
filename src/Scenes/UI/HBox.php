<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

/** Lays its children left to right. */
class HBox extends BoxContainer
{
    protected function vertical(): bool { return false; }
}
