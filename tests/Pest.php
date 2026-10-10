<?php
declare(strict_types=1);

/*
| Terran has no application in this suite. Tests build GameLoop, World,
| Viewport, TargetOpener and GameEngine from plain objects: a real
| EventLoop stack over StreamSelectWaiterBackend, Surface's DrawingManager
| over the native framebuffer and rasterize drivers, and FakeTarget as the
| OutputTarget. Nothing here opens a window or touches a panel.
*/

require_once __DIR__.'/Fixtures/Managers.php';   // first: FakeTarget mints through framebuffers()
require_once __DIR__.'/Fixtures/FakeTarget.php';
require_once __DIR__.'/Fixtures/RecordingScene.php';
require_once __DIR__.'/Fixtures/RecordingSession.php';
require_once __DIR__.'/Fixtures/FakeHumanInput.php';

use Surface\HumanInput\InputFrame;
use Tests\Fixtures\FakeTarget;
use Voyager\IOPools\LoopWaiter;
use Voyager\IOPools\PromiseEngines\GuzzlePromiseEngine;
use Voyager\IOPools\ResourceRegistry;
use Voyager\IOPools\Waiter\StreamSelectWaiterBackend;

function fakeTarget(int $width = 32, int $height = 16): FakeTarget
{
    return new FakeTarget($width, $height);
}

/** The three collaborators an EventLoop (and so a GameLoop) is built from. */
function testLoop(int $pace_ms = 1): array
{
    $registry = new ResourceRegistry();

    return [$registry, new LoopWaiter($registry, new StreamSelectWaiterBackend(), $pace_ms * 1_000_000), new GuzzlePromiseEngine()];
}

/** An InputFrame for hand-driven devices: 60 Hz frame length, clock from 0. */
function inputFrame(): InputFrame
{
    return new InputFrame(fn (): int => 16_666_667, 0);
}
