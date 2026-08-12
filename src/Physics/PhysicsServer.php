<?php

namespace ScrapyardIO\GameEngine\Physics;

/**
 * Dispatches physics resolution to a parallel worker on ZTS+parallel builds,
 * or falls back to a synchronous inline call on non-ZTS builds.
 *
 * The public contract is intentionally narrow: callers hand off a plain array
 * snapshot (no objects, no closures) and receive back a plain array result so
 * the data is always safe to cross thread boundaries.
 *
 * Full implementation of PhysicsResolver::step() and the snapshot/apply
 * helpers lives in the Physics sub-package (see PhysicsResolver.php,
 * PhysicsBounds2D, PhysicsObject2D).
 */
class PhysicsServer
{
    /** @var \parallel\Runtime|null */
    private mixed $runtime = null;

    public function __construct()
    {
        if (defined('PHP_ZTS') && PHP_ZTS && extension_loaded('parallel')) {
            // Discover the project's vendor/autoload.php from the list of
            // already-included files.  Laravel always requires it during
            // bootstrap, so it will always be present.  Without a bootstrap
            // the worker thread is "shared nothing" and cannot resolve
            // PhysicsResolver (or any Composer-managed class), causing
            // Future::value() to throw — and since that throw happens inside
            // a C-level blocking call the PHP signal handlers (pcntl) are
            // never delivered, making the process impossible to kill with
            // Ctrl+C.
            $bootstrap = null;

            foreach (get_included_files() as $file) {
                if (str_ends_with($file, '/vendor/autoload.php')) {
                    $bootstrap = $file;
                    break;
                }
            }

            $this->runtime = ! is_null($bootstrap)
                ? new \parallel\Runtime($bootstrap)
                : new \parallel\Runtime();
        }
    }

    /**
     * Submit a physics step.
     *
     * @param  array<int, array<string, mixed>>  $bounds_snapshot
     * @return \parallel\Future|array<int, array<string, mixed>>
     *
     * On ZTS+parallel a Future is returned so the main thread can overlap
     * _processInternal() while physics runs in the worker.
     * On non-ZTS the result is a plain array returned synchronously.
     */
    public function submitStep(array $bounds_snapshot, float $delta): mixed
    {
        if (! is_null($this->runtime)) {
            return $this->runtime->run(
                static fn (array $snapshot, float $d): array =>
                    \ScrapyardIO\GameEngine\Physics\PhysicsResolver::step($snapshot, $d),
                [$bounds_snapshot, $delta],
            );
        }

        return PhysicsResolver::step($bounds_snapshot, $delta);
    }
}
