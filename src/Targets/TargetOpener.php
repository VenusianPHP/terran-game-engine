<?php
declare(strict_types=1);
namespace Terran\Targets;

use Closure;
use Surface\Bridge\BridgedToolkitSession;
use Surface\Contracts\Windows\Primitives\TKCanvas;
use Surface\Windows\StagedWindow;
use Terran\TerranException;
use Voyager\Contracts\Vessel\TheServiceContainer;

/**
 * A config entry into an OutputTarget, by 'kind'. Three kinds are built in and
 * each goes through Surface's own manager: 'staged' opens a staged window
 * through app('staged-windows'), 'display' conjures a panel through
 * app('displays'), 'canvas' takes a TKCanvas the application already owns.
 * Packages add kinds with extend(). Terran names no toolkit, stager or panel.
 *
 * A staged window only shows and answers while its toolkit session is pumped,
 * so the first staged window on a session joins that session to
 * app('event-loop'), and closing the last one Terran opened there leaves it.
 */
class TargetOpener
{
    /** @var array<string, Closure(string, array<string, mixed>, TheServiceContainer): OwnedTarget> */
    protected array $openers = [];

    /** @var \SplObjectStorage<BridgedToolkitSession, int> joined session → staged windows open on it */
    protected \SplObjectStorage $sessions;

    /** @param string|null $stager terran.stager: the stager a staged target opens through when its options name none; null leaves it to Surface */
    public function __construct(protected readonly TheServiceContainer $app, protected readonly ?string $stager = null)
    {
        $this->sessions = new \SplObjectStorage();
        $this->openers['staged'] = $this->openStaged(...);
        $this->openers['canvas'] = $this->takeCanvas(...);
        $this->openers['display'] = $this->openDisplay(...);
    }

    /** @param array<string, mixed> $config */
    public function open(string $name, array $config): OwnedTarget
    {
        $known = implode(', ', $this->kinds());
        $kind = $config['kind'] ?? throw new TerranException("Target '{$name}' has no 'kind'. Known kinds: {$known}.");
        $opener = $this->openers[$kind] ?? throw new TerranException("Target '{$name}' has kind '{$kind}'. Known kinds: {$known}.");

        return $opener($name, $config, $this->app);
    }

    /** @param Closure(string, array<string, mixed>, TheServiceContainer): OwnedTarget $opener */
    public function extend(string $kind, Closure $opener): static
    {
        $this->openers[$kind] = $opener;

        return $this;
    }

    /** @return list<string> */
    public function kinds(): array { return array_keys($this->openers); }

    /** @param array<string, mixed> $config */
    protected function openStaged(string $name, array $config, TheServiceContainer $app): OwnedTarget
    {
        $width = $config['width'] ?? null;
        $height = $config['height'] ?? null;
        if (! is_int($width) || ! is_int($height) || $width <= 0 || $height <= 0) {
            throw new TerranException("Target '{$name}' of kind 'staged' takes 'width' and 'height' as positive ints; got ".var_export($width, true).' and '.var_export($height, true).'.');
        }

        $options = $config['options'] ?? [];
        if (! is_array($options)) {
            throw new TerranException("Target '{$name}' of kind 'staged' takes 'options' as an array; got ".get_debug_type($options).'.');
        }
        if (isset($config['title'])) {
            $options['title'] = (string) $config['title'];
        }
        if (! array_key_exists('toolkit', $options) && ! is_null($this->stager)) {
            $options['toolkit'] = $this->stager;
        }

        $manager = $app->make('staged-windows');
        $window = $manager->open($name, $width, $height, $options);
        if (! $window instanceof StagedWindow) {
            $window->close();
            throw new TerranException("Target '{$name}' of kind 'staged' joins its window's toolkit session to the loop, which takes a ".StagedWindow::class.'; the stager returned '.get_debug_type($window).'.');
        }

        $session = $window->session();
        $this->join($session, $app);

        return new OwnedTarget($name, $window, $this->engineOf($config), $name, function () use ($window, $session): void {
            if ($window->isOpen()) {
                $window->close();
            }
            $this->leave($session);
        }, $window->isOpen(...));
    }

    protected function join(BridgedToolkitSession $session, TheServiceContainer $app): void
    {
        if (! $this->sessions->contains($session)) {
            $session->joinLoop($app->make('event-loop'));
            $this->sessions[$session] = 0;
        }
        $this->sessions[$session] = $this->sessions[$session] + 1;
    }

    protected function leave(BridgedToolkitSession $session): void
    {
        if (! $this->sessions->contains($session)) {
            return;
        }
        $this->sessions[$session] = $this->sessions[$session] - 1;
        if ($this->sessions[$session] === 0) {
            $this->sessions->detach($session);
            $session->leaveLoop();
        }
    }

    /** @param array<string, mixed> $config */
    protected function takeCanvas(string $name, array $config, TheServiceContainer $app): OwnedTarget
    {
        $canvas = $config['canvas'] ?? null;
        if (! $canvas instanceof TKCanvas) {
            throw new TerranException("Target '{$name}' of kind 'canvas' takes 'canvas', a TKCanvas; got ".get_debug_type($canvas).'.');
        }

        return new OwnedTarget($name, $canvas, $this->engineOf($config), null, null);
    }

    /** @param array<string, mixed> $config */
    protected function openDisplay(string $name, array $config, TheServiceContainer $app): OwnedTarget
    {
        $panel = $config['panel'] ?? throw new TerranException("Target '{$name}' of kind 'display' takes 'panel', the catalog name of a display panel.");
        $manager = $app->make('displays');
        $display = $manager->panel((string) $panel, isset($config['config']) ? (string) $config['config'] : null, $name, (bool) ($config['direct'] ?? false));

        return new OwnedTarget($name, $display, $this->engineOf($config), null, fn () => $manager->detach($name));
    }

    /** @param array<string, mixed> $config */
    protected function engineOf(array $config): ?string
    {
        return isset($config['engine']) ? (string) $config['engine'] : null;
    }
}
