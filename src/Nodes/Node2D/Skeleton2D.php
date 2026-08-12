<?php

namespace ScrapyardIO\GameEngine\Nodes\Node2D;

use ScrapyardIO\GameEngine\Nodes\Node2D;

class Skeleton2D extends Node2D
{
    /** @return list<Bone2D> */
    public function bones(): array
    {
        $bones = [];

        foreach ($this->children() as $child) {
            if ($child instanceof Bone2D) {
                $bones[] = $child;
            }
        }

        return $bones;
    }
}