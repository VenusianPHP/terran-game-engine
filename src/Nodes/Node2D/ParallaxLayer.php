<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class ParallaxLayer extends Node2D
{
    public float $motionScaleX = 1.0;
    public float $motionScaleY = 1.0;
    public float $motionOffsetX = 0.0;
    public float $motionOffsetY = 0.0;
}