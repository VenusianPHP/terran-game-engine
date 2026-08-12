<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

class DampedSpringJoint2D extends Joint2D
{
    public float $restLength = 0.0;
    public float $stiffness = 20.0;
    public float $damping = 1.0;
}