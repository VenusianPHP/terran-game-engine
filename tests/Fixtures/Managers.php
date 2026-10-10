<?php
declare(strict_types=1);

use Surface\Drawing\DrawingManager;
use Surface\Framebuffers\FramebufferManager;
use Surface\Rasterize\RasterizeManager;

function fakeConfig(array $items): object
{
    return new class($items) {
        public function __construct(private readonly array $items) {}

        public function get(string $key, mixed $default = null): mixed
        {
            return $this->items[$key] ?? $default;
        }
    };
}

function framebuffers(array $config = []): FramebufferManager
{
    return new class($config) extends FramebufferManager {
        public function __construct(array $config) { $this->config = fakeConfig($config); }
    };
}

function rasterize(array $config = []): RasterizeManager
{
    return new class($config) extends RasterizeManager {
        public function __construct(array $config) { $this->config = fakeConfig($config); }
    };
}

/** A drawing manager with Velvet built in, over native framebuffers and rasterizing. */
function drawing(array $config = []): DrawingManager
{
    return new DrawingManager(fakeConfig($config), framebuffers($config), rasterize($config));
}
