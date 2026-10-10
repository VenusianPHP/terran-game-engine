<?php
declare(strict_types=1);

use Terran\Math\Basis;
use Terran\Math\Projection;
use Terran\Math\Transform3D;
use Terran\Math\Vector2;
use Terran\Math\Vector3;
use Terran\TerranException;

function near3(Vector3 $v, float $x, float $y, float $z): bool
{
    return abs($v->x - $x) < 1e-9 && abs($v->y - $y) < 1e-9 && abs($v->z - $z) < 1e-9;
}

it('does 2D vector arithmetic', function () {
    $a = new Vector2(3.0, 4.0);
    $r = (new Vector2(1.0, 0.0))->rotated(M_PI / 2);

    expect($a->length())->toBe(5.0)
        ->and($a->plus(new Vector2(1.0, 1.0)))->toEqual(new Vector2(4.0, 5.0))
        ->and($a->minus(new Vector2(1.0, 1.0)))->toEqual(new Vector2(2.0, 3.0))
        ->and($a->times(2.0))->toEqual(new Vector2(6.0, 8.0))
        ->and($a->normalized())->toEqual(new Vector2(0.6, 0.8))
        ->and(Vector2::zero()->normalized())->toEqual(Vector2::zero())
        ->and(abs($r->x) < 1e-12 && abs($r->y - 1.0) < 1e-12)->toBeTrue()
        ->and(Vector2::zero()->lerp(new Vector2(10.0, 20.0), 0.25))->toEqual(new Vector2(2.5, 5.0));
});

it('does 3D vector arithmetic', function () {
    $x = new Vector3(1.0, 0.0, 0.0);
    $y = new Vector3(0.0, 1.0, 0.0);

    expect($x->cross($y))->toEqual(new Vector3(0.0, 0.0, 1.0))
        ->and($x->dot($y))->toBe(0.0)
        ->and((new Vector3(2.0, 3.0, 6.0))->length())->toBe(7.0)
        ->and((new Vector3(0.0, 0.0, 5.0))->normalized())->toEqual(new Vector3(0.0, 0.0, 1.0))
        ->and(Vector3::zero()->normalized())->toEqual(Vector3::zero())
        ->and($x->plus($y)->minus($x)->times(3.0))->toEqual(new Vector3(0.0, 3.0, 0.0))
        ->and(Vector3::zero()->lerp(new Vector3(4.0, 8.0, 12.0), 0.5))->toEqual(new Vector3(2.0, 4.0, 6.0));
});

it('turns -Z towards -X under a +90 degree yaw, as Godot does', function () {
    $basis = Basis::fromEuler(new Vector3(0.0, M_PI / 2, 0.0));

    expect(near3($basis->times(new Vector3(0.0, 0.0, -1.0)), -1.0, 0.0, 0.0))->toBeTrue()
        ->and(near3(Basis::axisAngle(new Vector3(0.0, 1.0, 0.0), M_PI / 2)->times(new Vector3(0.0, 0.0, -1.0)), -1.0, 0.0, 0.0))->toBeTrue();
});

it('composes, inverts and rotates bases', function () {
    $basis = Basis::fromEuler(new Vector3(0.3, -1.1, 0.7));
    $round = $basis->inverse()->multiply($basis);
    $scaled = new Basis(new Vector3(2.0, 0.0, 0.0), new Vector3(0.0, 3.0, 0.0), new Vector3(0.0, 0.0, 4.0));
    $rotated = Basis::identity()->rotated(new Vector3(0.0, 0.0, 1.0), M_PI / 2);

    expect(near3($round->x, 1.0, 0.0, 0.0) && near3($round->y, 0.0, 1.0, 0.0) && near3($round->z, 0.0, 0.0, 1.0))->toBeTrue()
        ->and($scaled->determinant())->toBe(24.0)
        ->and(near3($scaled->inverse()->times(new Vector3(2.0, 3.0, 4.0)), 1.0, 1.0, 1.0))->toBeTrue()
        ->and(near3($rotated->times(new Vector3(1.0, 0.0, 0.0)), 0.0, 1.0, 0.0))->toBeTrue();
});

it('refuses a singular basis and a look direction it cannot use', function () {
    $flat = new Basis(new Vector3(1.0, 0.0, 0.0), new Vector3(1.0, 0.0, 0.0), new Vector3(0.0, 0.0, 1.0));

    expect(fn () => $flat->inverse())->toThrow(TerranException::class, 'singular')
        ->and(fn () => Basis::lookingAt(Vector3::zero(), new Vector3(0.0, 1.0, 0.0)))->toThrow(TerranException::class, 'zero')
        ->and(fn () => Basis::lookingAt(new Vector3(0.0, 2.0, 0.0), new Vector3(0.0, 1.0, 0.0)))->toThrow(TerranException::class, 'parallel');
});

it('looks along a direction with -Z forward', function () {
    $basis = Basis::lookingAt(new Vector3(1.0, 0.0, 0.0), new Vector3(0.0, 1.0, 0.0));

    expect(near3($basis->times(new Vector3(0.0, 0.0, -1.0)), 1.0, 0.0, 0.0))->toBeTrue()
        ->and(near3($basis->y, 0.0, 1.0, 0.0))->toBeTrue();
});

it('applies, composes and inverts transforms', function () {
    $t = new Transform3D(Basis::fromEuler(new Vector3(0.0, M_PI / 2, 0.0)), new Vector3(1.0, 2.0, 3.0));
    $p = new Vector3(0.0, 0.0, -1.0);
    $look = Transform3D::identity()->lookingAt(new Vector3(0.0, 0.0, 5.0), new Vector3(0.0, 1.0, 0.0));

    expect(near3($t->apply($p), 0.0, 2.0, 3.0))->toBeTrue()
        ->and(near3($t->inverse()->apply($t->apply($p)), 0.0, 0.0, -1.0))->toBeTrue()
        ->and(near3($t->multiply($t->inverse())->apply($p), 0.0, 0.0, -1.0))->toBeTrue()
        ->and(near3($look->basis->times(new Vector3(0.0, 0.0, -1.0)), 0.0, 0.0, 1.0))->toBeTrue();
});

it('projects the near plane to -1 and the far plane to +1', function () {
    $p = Projection::perspective(90.0, 2.0, 1.0, 100.0);
    $o = Projection::orthographic(10.0, 2.0, 1.0, 11.0);

    expect(near3($p->project(new Vector3(0.0, 0.0, -1.0)), 0.0, 0.0, -1.0))->toBeTrue()
        ->and(near3($p->project(new Vector3(0.0, 0.0, -100.0)), 0.0, 0.0, 1.0))->toBeTrue()
        ->and(near3($p->project(new Vector3(2.0, 1.0, -1.0)), 1.0, 1.0, -1.0))->toBeTrue()
        ->and(near3($o->project(new Vector3(10.0, 5.0, -6.0)), 1.0, 1.0, 0.0))->toBeTrue()
        ->and(Projection::perspective(90.0, 1.0, 1.0, 10.0)->multiply(new Projection([1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1]))->toArray())
            ->toBe(Projection::perspective(90.0, 1.0, 1.0, 10.0)->toArray());
});

it('refuses projections it cannot build', function () {
    expect(fn () => Projection::perspective(0.0, 1.0, 1.0, 10.0))->toThrow(TerranException::class, 'fov')
        ->and(fn () => Projection::perspective(60.0, 1.0, 0.0, 10.0))->toThrow(TerranException::class, 'near')
        ->and(fn () => Projection::orthographic(10.0, 0.0, 1.0, 10.0))->toThrow(TerranException::class, 'aspect')
        ->and(fn () => new Projection([1.0]))->toThrow(TerranException::class, '16')
        ->and(fn () => Projection::perspective(90.0, 1.0, 1.0, 10.0)->project(Vector3::zero()))->toThrow(TerranException::class, 'camera plane');
});
