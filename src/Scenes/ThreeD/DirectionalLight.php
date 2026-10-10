<?php
declare(strict_types=1);
namespace Terran\Scenes\ThreeD;

use Surface\NutsAndBolts\Color;
use Terran\Math\Vector3;

/** Light from infinitely far away, shining along its global -Z. */
class DirectionalLight extends Scene3D
{
    public Color $color;

    public float $energy = 1.0;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->color = Color::hex('#FFFFFF');
    }

    public function direction(): Vector3
    {
        return $this->globalTransform()->basis->times(new Vector3(0.0, 0.0, -1.0))->normalized();
    }
}
