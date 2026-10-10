<?php
declare(strict_types=1);
namespace Terran\Scenes;

use Surface\Contracts\Fonts\GFXFont;

/** A face given directly, or a slug (null = fonts.default) resolved through the World's AssetCache, or app('terran.assets') while not live. */
trait ResolvesFont
{
    public GFXFont|string|null $font = null;

    protected function face(): GFXFont
    {
        if ($this->font instanceof GFXFont) {
            return $this->font;
        }
        $world = $this->world();

        return (is_null($world) ? app('terran.assets') : $world->assets())->font($this->font);
    }
}
