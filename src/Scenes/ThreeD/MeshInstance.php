<?php
declare(strict_types=1);
namespace Terran\Scenes\ThreeD;

use Surface\NutsAndBolts\Color;
use Terran\Assets\Mesh;
use Terran\Assets\Texture;

/** A Mesh placed in the world, with a colour and an optional texture. */
class MeshInstance extends Scene3D
{
    public Color $color;

    public ?Texture $texture = null;

    public function __construct(string $name, public ?Mesh $mesh = null)
    {
        parent::__construct($name);
        $this->color = Color::hex('#FFFFFF');
    }
}
