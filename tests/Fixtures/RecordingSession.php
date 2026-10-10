<?php
declare(strict_types=1);
namespace Tests\Fixtures;

use Surface\Bridge\BridgedToolkitSession;
use Voyager\Contracts\IOPools\Loop;

/** A toolkit session with no toolkit behind it: polled, never sleeps, records joinLoop()/leaveLoop() calls. */
final class RecordingSession extends BridgedToolkitSession
{
    /** @var list<string> */
    public array $calls = [];

    public function pump(int $budget_ns): int { return 0; }

    public function joinLoop(Loop $loop): void
    {
        $this->calls[] = 'join';
        parent::joinLoop($loop);
    }

    public function leaveLoop(): void
    {
        $this->calls[] = 'leave';
        parent::leaveLoop();
    }

    protected function sleepsNatively(): bool { return false; }

    protected function initializeEngine(): void {}

    protected function connectToEngine(): void {}

    protected function disconnectEngine(): void {}

    protected function wakeDescriptor(int $fd): void {}

    protected function releaseWakeDescriptor(): void {}
}
