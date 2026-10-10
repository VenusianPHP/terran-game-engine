<?php
declare(strict_types=1);

use Terran\GameLoop;
use Terran\TerranException;
use Terran\World;
use Tests\Fixtures\RecordingScene;

function world(): World
{
    [$registry, $waiter, $promises] = testLoop();

    return new World(new GameLoop($registry, $waiter, $promises));
}

it('readies the whole tree parent-first when the root is set, and attaches every scene to the loop', function () {
    $world = world();
    $root = new RecordingScene('root');
    $a = new RecordingScene('a');
    $b = new RecordingScene('b');
    $root->add($a);
    $a->add($b);

    $world->setRoot($root);

    expect($root->hooks())->toBe(['ready'])->and($a->hooks())->toBe(['ready'])->and($b->hooks())->toBe(['ready'])
        ->and($world->loop()->attached())->toBe([$root, $a, $b])
        ->and($b->world())->toBe($world);
});

it('refuses a second root', function () {
    $world = world()->setRoot(new RecordingScene('root'));
    expect(fn () => $world->setRoot(new RecordingScene('again')))->toThrow(TerranException::class, "World already has a root ('root'). destroy() it first.");
});

it('attaches a subtree added to a live tree, and exits one removed from it', function () {
    $world = world();
    $root = new RecordingScene('root');
    $world->setRoot($root);
    $late = new RecordingScene('late');
    $grandchild = new RecordingScene('g');
    $late->add($grandchild);

    $root->add($late);
    expect($late->hooks())->toBe(['ready'])->and($grandchild->hooks())->toBe(['ready'])
        ->and(count($world->loop()->attached()))->toBe(3);

    $root->remove($late);
    expect($late->hooks())->toBe(['ready', 'exit'])->and($grandchild->hooks())->toBe(['ready', 'exit'])
        ->and($world->loop()->attached())->toBe([$root])
        ->and($grandchild->world())->toBeNull();
});

it('ticks process() depth first with the frame delta', function () {
    $world = world();
    $root = new RecordingScene('root');
    $child = new RecordingScene('child');
    $root->add($child);
    $world->setRoot($root);

    $world->tick([], 0.02);

    expect($root->calls[1])->toBe(['process', 0.02])->and($child->calls[1])->toBe(['process', 0.02]);
});

it('renders every viewport and destroys down the tree', function () {
    $world = world();
    $one = fakeTarget();
    $two = fakeTarget();
    $drawing = drawing();
    $world->addViewport('one', $one, $drawing->renderer('velvet', ['output' => $one]));
    $world->addViewport('two', $two, $drawing->renderer('velvet', ['output' => $two]));
    $root = new RecordingScene('root');
    $world->setRoot($root);

    $world->render();
    expect($one->presented)->toBe(1)->and($two->presented)->toBe(1)->and(array_keys($world->viewports()))->toBe(['one', 'two']);

    $world->destroy();
    expect($root->hooks())->toBe(['ready', 'draw', 'draw', 'exit'])
        ->and($world->root())->toBeNull()->and($world->viewports())->toBe([])
        ->and($world->loop()->attached())->toBe([]);
});

it('refuses a viewport name taken twice, and tick/render before a root', function () {
    $world = world();
    $t = fakeTarget();
    $world->addViewport('main', $t, drawing()->renderer('velvet', ['output' => $t]));
    expect(fn () => $world->addViewport('main', $t, drawing()->renderer('velvet', ['output' => $t])))->toThrow(TerranException::class, "World already has a viewport named 'main'.")
        ->and(fn () => $world->tick([], 0.0))->toThrow(TerranException::class, 'World has no root Scene. setRoot() first.')
        ->and(fn () => $world->render())->toThrow(TerranException::class, 'World has no root Scene. setRoot() first.');
});

it('starts async work on the loop and hands back its Task', function () {
    $world = world();
    $promise = $world->loop()->promise();
    $task = $world->async(fn () => $world->loop()->await($promise) * 2);

    $promise->resolve(21);
    $world->loop()->await($promise);   // settle the promise on the loop before waiting on the task that awaits it
    $world->loop()->until(fn () => $task->settled());

    expect($task->wait())->toBe(42);
});

it('refuses a wait from process(), naming the fix', function () {
    $world = world();
    $promise = $world->loop()->promise();
    $root = new class('root') extends \Terran\Scene {
        public $promise;
        public function process(float $delta): void { $this->world()->loop()->await($this->promise); }
    };
    $root->promise = $promise;
    $world->setRoot($root);

    expect(fn () => $world->tick([], 0.016))->toThrow(TerranException::class, '$this->world()->async(...)');
});

it('finds the viewport for a window name', function () {
    $world = world();
    $t = fakeTarget();
    $world->addViewport('main', $t, drawing()->renderer('velvet', ['output' => $t]), null, null, 'main-window');
    expect($world->viewportForWindow('main-window')?->name)->toBe('main')
        ->and($world->viewportForWindow('other'))->toBeNull()
        ->and($world->viewportForWindow(null))->toBeNull();
});

it('skips process() and input() for a scene removed earlier in the same walk', function () {
    $world = world();
    $root = new RecordingScene('root');
    $b = new RecordingScene('b');
    $a = new class('a') extends \Terran\Scene {
        public ?\Terran\Scene $victim = null;
        public function process(float $delta): void { if ($this->victim->parent()) { $this->parent()->remove($this->victim); } }
    };
    $a->victim = $b;
    $root->add($a)->add($b);
    $world->setRoot($root);

    $world->tick([], 0.016);

    expect(array_filter($b->hooks(), fn ($h) => $h === 'process'))->toBe([]);
});

it('does not attach a child its parent removed in ready()', function () {
    $world = world();
    $child = new RecordingScene('child');
    $root = new class('root') extends \Terran\Scene {
        public function ready(): void { $this->remove($this->find('child')); }
    };
    $root->add($child);
    $world->setRoot($root);

    expect($world->loop()->attached())->toBe([$root])
        ->and($child->hooks())->toBe([])
        ->and($child->freed())->toBeTrue();
});

it('refuses to re-add a removed scene', function () {
    $world = world();
    $root = new RecordingScene('root');
    $child = new RecordingScene('child');
    $root->add($child);
    $world->setRoot($root);
    $root->remove($child);

    expect(fn () => $root->add($child))->toThrow(TerranException::class, "'child' was removed from its tree, and a removed Scene is not re-added. Add a new one.");
});
