<?php
declare(strict_types=1);

use Terran\Assets\Mesh;
use Terran\Math\Vector3;
use Terran\TerranException;

/** Every triangle's winding normal points the way its vertex normals do. */
function windsOutward(Mesh $mesh): bool
{
    foreach ($mesh->triangles as [$a, $b, $c]) {
        $n = $mesh->vertices[$b]->minus($mesh->vertices[$a])->cross($mesh->vertices[$c]->minus($mesh->vertices[$a]));
        if ($n->dot($mesh->normals[$a]) <= 0) {
            return false;
        }
    }

    return true;
}

it('builds a box of 24 vertices and 12 outward triangles', function () {
    $box = Mesh::box(2.0, 4.0, 6.0);
    $xs = array_map(fn (Vector3 $v) => $v->x, $box->vertices);
    $ys = array_map(fn (Vector3 $v) => $v->y, $box->vertices);
    $zs = array_map(fn (Vector3 $v) => $v->z, $box->vertices);

    expect(count($box->vertices))->toBe(24)
        ->and(count($box->triangles))->toBe(12)
        ->and([min($xs), max($xs), min($ys), max($ys), min($zs), max($zs)])->toBe([-1.0, 1.0, -2.0, 2.0, -3.0, 3.0])
        ->and(windsOutward($box))->toBeTrue()
        ->and(count($box->uvs))->toBe(24);
});

it('builds a plane on XZ facing +Y', function () {
    $plane = Mesh::plane(2.0, 2.0);

    expect(count($plane->triangles))->toBe(2)
        ->and($plane->normals[0])->toEqual(new Vector3(0.0, 1.0, 0.0))
        ->and(windsOutward($plane))->toBeTrue();
});

it('reads an OBJ polygon as a triangle fan with its uvs and normals, once per path', function () {
    $cache = assets();
    $quad = Mesh::fromObj(__DIR__.'/../../Fixtures/quad.obj', $cache);

    expect(count($quad->vertices))->toBe(4)
        ->and($quad->triangles)->toBe([[0, 1, 2], [0, 2, 3]])
        ->and($quad->normals[2])->toEqual(new Vector3(0.0, 0.0, 1.0))
        ->and(count($quad->uvs))->toBe(4)
        ->and(Mesh::fromObj(__DIR__.'/../../Fixtures/quad.obj', $cache))->toBe($quad);
});

it('computes normals when the file has none', function () {
    $path = sys_get_temp_dir().'/terran-tri-'.getmypid().'.obj';
    file_put_contents($path, "v 0 0 0\nv 1 0 0\nv 0 1 0\nf 1 2 3\n");
    $GLOBALS['terran_temp_files'][] = $path;
    $tri = Mesh::fromObj($path, assets());

    expect($tri->normals[0])->toEqual(new Vector3(0.0, 0.0, 1.0))
        ->and($tri->uvs)->toBeNull();
});

it('names the file and line of a face that names a missing vertex', function () {
    $path = realpath(__DIR__.'/../../Fixtures/broken.obj');

    expect(fn () => Mesh::fromObj($path, assets()))->toThrow(TerranException::class, "{$path}:4 names vertex 7; the file has 3 so far.");
});

it('refuses meshes it cannot build', function () {
    expect(fn () => Mesh::box(0.0, 1.0, 1.0))->toThrow(TerranException::class, 'positive')
        ->and(fn () => new Mesh([Vector3::zero()], [[0, 1, 2]]))->toThrow(TerranException::class, 'vertex 1')
        ->and(fn () => new Mesh([Vector3::zero()], [], [Vector3::zero(), Vector3::zero()]))->toThrow(TerranException::class, 'normals');
});
