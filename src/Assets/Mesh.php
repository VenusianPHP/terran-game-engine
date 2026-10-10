<?php
declare(strict_types=1);
namespace Terran\Assets;

use Terran\Math\Vector2;
use Terran\Math\Vector3;
use Terran\TerranException;

/** Triangles over vertices, counter-clockwise front faces, with per-vertex normals. */
final class Mesh
{
    /** @var list<Vector3> */
    public readonly array $normals;

    /**
     * @param list<Vector3> $vertices
     * @param list<array{int, int, int}> $triangles
     * @param list<Vector3>|null $normals computed from the faces when null
     * @param list<Vector2>|null $uvs
     */
    public function __construct(
        public readonly array $vertices,
        public readonly array $triangles,
        ?array $normals = null,
        public readonly ?array $uvs = null,
    ) {
        $count = count($vertices);
        foreach ($triangles as $triangle) {
            foreach ($triangle as $index) {
                if ($index < 0 || $index >= $count) {
                    throw new TerranException("A triangle names vertex {$index}; the mesh has vertices 0 to ".($count - 1).'.');
                }
            }
        }
        if (! is_null($normals) && count($normals) !== $count) {
            throw new TerranException("A mesh has one of its normals per vertex: {$count} vertices, ".count($normals).' normals.');
        }
        if (! is_null($uvs) && count($uvs) !== $count) {
            throw new TerranException("A mesh has one uv per vertex: {$count} vertices, ".count($uvs).' uvs.');
        }
        $this->normals = $normals ?? self::faceNormals($vertices, $triangles);
    }

    public static function box(float $width, float $height, float $depth): self
    {
        self::positive(['width' => $width, 'height' => $height, 'depth' => $depth]);
        $half = new Vector3($width / 2, $height / 2, $depth / 2);
        // normal, u, v with u × v = normal
        $faces = [
            [new Vector3(1, 0, 0), new Vector3(0, 0, -1), new Vector3(0, 1, 0)],
            [new Vector3(-1, 0, 0), new Vector3(0, 0, 1), new Vector3(0, 1, 0)],
            [new Vector3(0, 1, 0), new Vector3(1, 0, 0), new Vector3(0, 0, -1)],
            [new Vector3(0, -1, 0), new Vector3(1, 0, 0), new Vector3(0, 0, 1)],
            [new Vector3(0, 0, 1), new Vector3(1, 0, 0), new Vector3(0, 1, 0)],
            [new Vector3(0, 0, -1), new Vector3(-1, 0, 0), new Vector3(0, 1, 0)],
        ];
        $extent = fn (Vector3 $axis): float => abs($axis->x) * $half->x + abs($axis->y) * $half->y + abs($axis->z) * $half->z;
        [$vertices, $normals, $uvs, $triangles] = [[], [], [], []];
        foreach ($faces as [$n, $u, $v]) {
            $centre = $n->times($extent($n));
            $du = $u->times($extent($u));
            $dv = $v->times($extent($v));
            $base = count($vertices);
            array_push($vertices, $centre->minus($du)->minus($dv), $centre->plus($du)->minus($dv), $centre->plus($du)->plus($dv), $centre->minus($du)->plus($dv));
            array_push($normals, $n, $n, $n, $n);
            array_push($uvs, new Vector2(0.0, 1.0), new Vector2(1.0, 1.0), new Vector2(1.0, 0.0), new Vector2(0.0, 0.0));
            array_push($triangles, [$base, $base + 1, $base + 2], [$base, $base + 2, $base + 3]);
        }

        return new self($vertices, $triangles, $normals, $uvs);
    }

    public static function plane(float $width, float $depth): self
    {
        self::positive(['width' => $width, 'depth' => $depth]);
        [$x, $z] = [$width / 2, $depth / 2];
        $up = new Vector3(0.0, 1.0, 0.0);

        return new self(
            [new Vector3(-$x, 0.0, -$z), new Vector3($x, 0.0, -$z), new Vector3($x, 0.0, $z), new Vector3(-$x, 0.0, $z)],
            [[0, 2, 1], [0, 3, 2]],
            [$up, $up, $up, $up],
            [new Vector2(0.0, 0.0), new Vector2(1.0, 0.0), new Vector2(1.0, 1.0), new Vector2(0.0, 1.0)],
        );
    }

    /** Wavefront OBJ: v, vt, vn and f (i, i/t, i//n, i/t/n, negative indexes); polygons fan into triangles; other lines are skipped. */
    public static function fromObj(string $path, ?AssetCache $cache = null): self
    {
        return ($cache ?? app('terran.assets'))->mesh($path, fn (string $real): self => self::parseObj($real));
    }

    private static function parseObj(string $path): self
    {
        [$positions, $texcoords, $directions] = [[], [], []];
        [$vertices, $normals, $uvs, $triangles, $corners] = [[], [], [], [], []];
        [$every_normal, $every_uv] = [true, true];
        foreach (preg_split('/\R/', (string) file_get_contents($path)) as $i => $line) {
            $parts = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
            $at = $path.':'.($i + 1);
            switch ($parts[0] ?? '') {
                case 'v':
                    $positions[] = new Vector3(...self::numbers($parts, 3, $at));
                    break;
                case 'vt':
                    $texcoords[] = new Vector2(...self::numbers($parts, 2, $at));
                    break;
                case 'vn':
                    $directions[] = new Vector3(...self::numbers($parts, 3, $at));
                    break;
                case 'f':
                    if (count($parts) < 4) {
                        throw new TerranException("{$at} has a face of fewer than 3 corners.");
                    }
                    $face = [];
                    foreach (array_slice($parts, 1) as $token) {
                        $refs = explode('/', $token);
                        $v = self::index($refs[0], count($positions), $at, 'vertex');
                        $t = ($refs[1] ?? '') === '' ? null : self::index($refs[1], count($texcoords), $at, 'uv');
                        $n = ($refs[2] ?? '') === '' ? null : self::index($refs[2], count($directions), $at, 'normal');
                        $every_uv = $every_uv && ! is_null($t);
                        $every_normal = $every_normal && ! is_null($n);
                        $key = "{$v}/{$t}/{$n}";
                        if (! isset($corners[$key])) {
                            $corners[$key] = count($vertices);
                            $vertices[] = $positions[$v];
                            $uvs[] = is_null($t) ? null : $texcoords[$t];
                            $normals[] = is_null($n) ? null : $directions[$n];
                        }
                        $face[] = $corners[$key];
                    }
                    for ($k = 1; $k < count($face) - 1; $k++) {
                        $triangles[] = [$face[0], $face[$k], $face[$k + 1]];
                    }
                    break;
            }
        }

        return new self($vertices, $triangles, $every_normal && $vertices !== [] ? $normals : null, $every_uv && $vertices !== [] ? $uvs : null);
    }

    /** @return list<float> */
    private static function numbers(array $parts, int $count, string $at): array
    {
        $values = array_slice($parts, 1, $count);
        if (count($values) < $count || array_filter($values, fn (string $v): bool => ! is_numeric($v)) !== []) {
            throw new TerranException("{$at} needs {$count} numbers after '{$parts[0]}'.");
        }

        return array_map('floatval', $values);
    }

    private static function index(string $token, int $count, string $at, string $what): int
    {
        if (! preg_match('/^-?\d+$/', $token)) {
            throw new TerranException("{$at} has '{$token}' where a {$what} index belongs.");
        }
        $n = (int) $token;
        $index = $n < 0 ? $count + $n : $n - 1;
        if ($n === 0 || $index < 0 || $index >= $count) {
            throw new TerranException("{$at} names {$what} {$n}; the file has {$count} so far.");
        }

        return $index;
    }

    /**
     * @param list<Vector3> $vertices
     * @param list<array{int, int, int}> $triangles
     * @return list<Vector3>
     */
    private static function faceNormals(array $vertices, array $triangles): array
    {
        $sums = array_fill(0, count($vertices), Vector3::zero());
        foreach ($triangles as [$a, $b, $c]) {
            $n = $vertices[$b]->minus($vertices[$a])->cross($vertices[$c]->minus($vertices[$a]));
            foreach ([$a, $b, $c] as $i) {
                $sums[$i] = $sums[$i]->plus($n);
            }
        }

        return array_map(fn (Vector3 $v): Vector3 => $v->normalized(), $sums);
    }

    /** @param array<string, float> $sizes */
    private static function positive(array $sizes): void
    {
        foreach ($sizes as $name => $size) {
            if ($size <= 0.0) {
                throw new TerranException("A mesh {$name} is positive; got {$size}.");
            }
        }
    }
}
