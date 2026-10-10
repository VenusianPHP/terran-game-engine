<?php
declare(strict_types=1);

use Surface\Contracts\Drawing\RenderingEngine;
use Terran\Assets\Mesh;
use Terran\Math\Projection;
use Terran\Math\Vector3;
use Terran\Scenes\ThreeD\Camera3D;
use Terran\Scenes\ThreeD\CameraProjection;
use Terran\Scenes\ThreeD\DirectionalLight;
use Terran\Scenes\ThreeD\MeshInstance;
use Terran\Scenes\ThreeD\Scene3D;
use Terran\Scenes\TwoD\Camera2D;
use Terran\Scenes\TwoD\Scene2D;
use Terran\TerranException;

function close3(Vector3 $v, float $x, float $y, float $z): bool
{
    return abs($v->x - $x) < 1e-9 && abs($v->y - $y) < 1e-9 && abs($v->z - $z) < 1e-9;
}

it('composes transforms down the Scene3D chain', function () {
    $parent = (new Scene3D('parent'))->translate(new Vector3(1.0, 0.0, 0.0))->rotateY(M_PI / 2);
    $child = (new Scene3D('child'))->translate(new Vector3(0.0, 0.0, -1.0));
    $parent->add($child);

    expect(close3($child->globalTransform()->origin, 0.0, 0.0, 0.0))->toBeTrue()
        ->and(close3($parent->globalTransform()->origin, 1.0, 0.0, 0.0))->toBeTrue();
});

it('looks at a global target from inside a moved parent', function () {
    $parent = (new Scene3D('parent'))->translate(new Vector3(0.0, 0.0, 10.0));
    $eye = new Scene3D('eye');
    $parent->add($eye);
    $eye->lookAt(new Vector3(5.0, 0.0, 10.0), new Vector3(0.0, 1.0, 0.0));

    expect(close3($eye->globalTransform()->basis->times(new Vector3(0.0, 0.0, -1.0)), 1.0, 0.0, 0.0))->toBeTrue();
});

it('hides a subtree under an invisible Scene3D', function () {
    $parent = new Scene3D('parent');
    $parent->visible = false;
    $child = new Scene3D('child');
    $parent->add($child);

    expect($child->visibleInTree())->toBeFalse();
});

it('builds its projection and view', function () {
    $camera = (new Camera3D('c'))->translate(new Vector3(0.0, 0.0, 5.0));
    $ortho = new Camera3D('o');
    $ortho->projectionKind = CameraProjection::ORTHOGRAPHIC;
    $ortho->size = 4.0;

    expect($camera->projection(2.0)->toArray())->toBe(Projection::perspective(70.0, 2.0, 0.05, 1000.0)->toArray())
        ->and($ortho->projection(1.0)->toArray())->toBe(Projection::orthographic(4.0, 1.0, 0.05, 1000.0)->toArray())
        ->and(close3($camera->view()->apply(new Vector3(0.0, 0.0, 5.0)), 0.0, 0.0, 0.0))->toBeTrue();
});

it('binds a current Camera3D beside a current Camera2D, and releases it on removal', function () {
    [$world] = canvasWorld();
    $root = new Scene2D('root');
    $c2 = (new Camera2D('c2'))->makeCurrent();
    $c3 = (new Camera3D('c3'))->makeCurrent();
    $root->add($c2)->add($c3);
    $world->setRoot($root);
    $both = [$world->viewport('main')->camera2d(), $world->viewport('main')->camera3d()];
    $root->remove($c3);

    expect($both)->toBe([$c2, $c3])
        ->and($world->viewport('main')->camera3d())->toBeNull()
        ->and($world->viewport('main')->camera2d())->toBe($c2);
});

it('names the viewports when a current Camera3D names a missing one', function () {
    [$world] = canvasWorld();
    $root = new Scene2D('root');
    $root->add((new Camera3D('c3', 'hud'))->makeCurrent());
    $world->setRoot($root);

    expect(fn () => $world->render())->toThrow(TerranException::class, "Camera3D 'c3' names viewport 'hud', which this World does not have. Viewports: main.");
});

it('shines a DirectionalLight along its global -Z', function () {
    $light = (new DirectionalLight('sun'))->rotateY(M_PI / 2);

    expect(close3($light->direction(), -1.0, 0.0, 0.0))->toBeTrue()
        ->and($light->energy)->toBe(1.0);
});

it('carries a mesh, and the Viewport draws no 3D Scene before slice 6', function () {
    [$world, $target] = canvasWorld(8, 8);
    $root = new Scene2D('root');
    $box = new class('box', Mesh::box(1.0, 1.0, 1.0)) extends MeshInstance {
        public int $draws = 0;

        public function draw(RenderingEngine $ge): void { $this->draws++; }
    };
    $root->add($box);
    $world->setRoot($root);
    $world->render();

    expect($box->draws)->toBe(0)
        ->and(count($box->mesh->triangles))->toBe(12)
        ->and(hexAt($target->framebuffer(), 4, 4))->toBe('000000FF');
});

it('binds a live Camera3D when current is set true, and releases it when set false', function () {
    [$world] = canvasWorld();
    $root = new Scene2D('root');
    $camera = new Camera3D('c3');
    $root->add($camera);
    $world->setRoot($root);
    $camera->current = true;
    $bound = $world->viewport('main')->camera3d();
    $camera->current = false;

    expect($bound)->toBe($camera)->and($world->viewport('main')->camera3d())->toBeNull();
});
