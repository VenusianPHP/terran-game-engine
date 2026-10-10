<?php
declare(strict_types=1);
namespace Terran\Scenes\UI;

/** What a mouse event inside a control does: STOP receives it and ends the walk, PASS receives it and the walk goes on, IGNORE never receives one. */
enum MouseFilter
{
    case STOP;
    case PASS;
    case IGNORE;
}
