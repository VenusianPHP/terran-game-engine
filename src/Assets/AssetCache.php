<?php
declare(strict_types=1);
namespace Terran\Assets;

use Closure;
use Surface\Contracts\Fonts\GFXFont;
use Surface\Contracts\Framebuffers\FormatSpec;
use Surface\Contracts\Framebuffers\Framebuffer;
use Surface\Contracts\Framebuffers\FramebufferDriver;
use Surface\Contracts\Images\ImageDecoder;
use Surface\Fonts\FontManager;
use Terran\TerranException;
use Throwable;

/**
 * Decodes each file once per run, keyed by real path, and makes the blank
 * RGBA8 framebuffers sheets are cut into. Bound as terran.assets; the World
 * hands it to Scenes.
 */
final class AssetCache
{
    /** @var array<string, Texture> */
    private array $textures = [];

    /** @var array<string, Mesh> */
    private array $meshes = [];

    public function __construct(
        private readonly ImageDecoder $decoder,
        private readonly FramebufferDriver $framebuffers,
        private readonly FontManager $fonts,
    ) {}

    public function texture(string $path): Texture
    {
        $key = $this->key($path);
        if (isset($this->textures[$key])) {
            return $this->textures[$key];
        }
        try {
            $framebuffer = $this->decoder->decode((string) file_get_contents($key));
        } catch (Throwable $e) {
            throw new TerranException("Texture '{$path}' did not decode: {$e->getMessage()}", 0, $e);
        }

        return $this->textures[$key] = Texture::of($framebuffer);
    }

    /** @param Closure(string): Mesh $load parses the real path */
    public function mesh(string $path, Closure $load): Mesh
    {
        $key = $this->key($path);

        return $this->meshes[$key] ??= $load($key);
    }

    public function blank(int $width, int $height): Framebuffer
    {
        return $this->framebuffers->full(FormatSpec::rgba8(), $width, $height);
    }

    /** A face by slug; null is fonts.default. */
    public function font(?string $slug): GFXFont
    {
        if (! is_null($slug) && ! $this->fonts->has($slug)) {
            throw new TerranException("No font face '{$slug}'. Faces: ".implode(', ', $this->fonts->slugs()).'.');
        }

        return $this->fonts->face($slug);
    }

    public function forget(): void
    {
        $this->textures = [];
        $this->meshes = [];
    }

    private function key(string $path): string
    {
        $real = realpath($path);
        if ($real === false || ! is_file($real)) {
            throw new TerranException("Asset file '{$path}' does not exist.");
        }

        return $real;
    }
}
