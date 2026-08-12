<?php

use ScrapyardIO\GameEngine\Nodes\Node2D;
use ScrapyardIO\GameEngine\Nodes\Node2D as N;

$classes = [
    N\AnimatedSprite2D::class,
    N\AudioListener2D::class,
    N\AudioStreamPlayer2D::class,
    N\BackBufferCopy::class,
    N\Bone2D::class,
    N\Camera2D::class,
    N\CanvasGroup::class,
    N\CanvasModulate::class,
    N\CollisionPolygon2D::class,
    N\CollisionShape2D::class,
    N\CPUParticles2D::class,
    N\GPUParticles2D::class,
    N\PinJoint2D::class,
    N\GrooveJoint2D::class,
    N\DampedSpringJoint2D::class,
    N\PointLight2D::class,
    N\DirectionalLight2D::class,
    N\LightOccluder2D::class,
    N\Line2D::class,
    N\Marker2D::class,
    N\MeshInstance2D::class,
    N\MultiMeshInstance2D::class,
    N\NavigationLink2D::class,
    N\NavigationObstacle2D::class,
    N\NavigationRegion2D::class,
    N\Parallax2D::class,
    N\ParallaxLayer::class,
    N\Path2D::class,
    N\PathFollow2D::class,
    N\Polygon2D::class,
    N\RayCast2D::class,
    N\RemoteTransform2D::class,
    N\ShapeCast2D::class,
    N\Skeleton2D::class,
    N\Sprite2D::class,
    N\TileMap::class,
    N\TileMapLayer::class,
    N\TouchScreenButton::class,
    N\VisibleOnScreenNotifier2D::class,
    N\VisibleOnScreenEnabler2D::class,
    N\Area2D::class,
    N\StaticBody2D::class,
    N\CharacterBody2D::class,
    N\RigidBody2D::class,
    N\AnimatableBody2D::class,
    N\PhysicsBounds2D::class,
];

it('autoloads Node2D catalog classes', function (string $class): void {
    expect(class_exists($class))->toBeTrue();
    $ref = new ReflectionClass($class);

    if ($ref->isAbstract()) {
        expect($ref->isAbstract())->toBeTrue();

        return;
    }

    $instance = new $class;
    expect($instance)->toBeInstanceOf(Node2D::class);
})->with($classes);
