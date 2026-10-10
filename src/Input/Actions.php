<?php
declare(strict_types=1);
namespace Terran\Input;

/** Action state a Scene reads: the frame's snapshot in process(), the physics latch in physicsProcess(). */
interface Actions
{
    public function isDown(string $action): bool;

    public function isPressed(string $action): bool;

    public function wasReleased(string $action): bool;

    /** 0..1: an axis binding's magnitude past its dead zone, rescaled; a button 0 or 1. The strongest binding wins. */
    public function strength(string $action): float;

    /** strength($positive) − strength($negative). */
    public function axis(string $negative, string $positive): float;
}
