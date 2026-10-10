<?php
declare(strict_types=1);
namespace Terran\Assets;

use Surface\Contracts\Framebuffers\Framebuffer;

/** One decoded image. */
final class Texture
{
    private function __construct(private readonly Framebuffer $framebuffer) {}

    public static function of(Framebuffer $framebuffer): self { return new self($framebuffer); }

    /** Decoded once per path through $cache, or app('terran.assets'). */
    public static function load(string $path, ?AssetCache $cache = null): self
    {
        return ($cache ?? app('terran.assets'))->texture($path);
    }

    public function width(): int { return $this->framebuffer->viewportWidth(); }

    public function height(): int { return $this->framebuffer->viewportHeight(); }

    public function framebuffer(): Framebuffer { return $this->framebuffer; }
}
