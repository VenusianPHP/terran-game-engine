<?php
declare(strict_types=1);
namespace Tests\Fixtures;

use Surface\Contracts\Drawing\OutputTarget;
use Surface\Contracts\Framebuffers\DamageTrackingFramebuffer;
use Surface\Contracts\Framebuffers\FormatSpec;
use Surface\Contracts\Framebuffers\Framebuffer;

/** An RGBA8 output target over a native dirty framebuffer; counts present() calls. */
final class FakeTarget implements OutputTarget
{
    public int $presented = 0;

    private DamageTrackingFramebuffer $framebuffer;

    public function __construct(public int $width, public int $height)
    {
        // DirtyFramebuffer is abstract; the native driver mints the concrete one.
        $this->framebuffer = framebuffers()->driver('native')->dirty(FormatSpec::rgba8(), $width, $height);
    }

    /** As a window resize does: the next framebuffer() is a new one at the new size. */
    public function resize(int $width, int $height): void
    {
        $this->width = $width;
        $this->height = $height;
        if ($width > 0 && $height > 0) {
            $this->framebuffer = framebuffers()->driver('native')->dirty(FormatSpec::rgba8(), $width, $height);
        }
    }

    public function boundFramebuffer(): ?Framebuffer { return $this->framebuffer; }

    public function framebuffer(): Framebuffer { return $this->framebuffer; }

    public function pixelSize(): array { return [$this->width, $this->height]; }

    public function pixelFormat(): FormatSpec { return FormatSpec::rgba8(); }

    public function present(): static
    {
        $this->presented++;
        $this->framebuffer->beginEpoch();

        return $this;
    }
}
