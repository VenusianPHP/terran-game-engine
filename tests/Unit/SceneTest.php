<?php
declare(strict_types=1);

use Terran\TerranException;
use Tests\Fixtures\RecordingScene;

it('builds a tree with parents, paths and depth-first walk', function () {
    $root = new RecordingScene('root');
    $a = new RecordingScene('a');
    $b = new RecordingScene('b');
    $c = new RecordingScene('c');
    $root->add($a)->add($b);
    $a->add($c);

    expect($c->parent())->toBe($a)
        ->and($c->path())->toBe('root/a/c')
        ->and($root->find('a/c'))->toBe($c)
        ->and($root->find('a/nope'))->toBeNull()
        ->and(array_map(fn ($s) => $s->name, $root->walk()))->toBe(['root', 'a', 'c', 'b']);
});

it('refuses a child that already has a parent, and a duplicate child name', function () {
    $root = new RecordingScene('root');
    $other = new RecordingScene('other');
    $a = new RecordingScene('a');
    $root->add($a);

    expect(fn () => $other->add($a))->toThrow(TerranException::class, "'a' is already a child of 'root'.")
        ->and(fn () => $root->add(new RecordingScene('a')))->toThrow(TerranException::class, "'root' already has a child named 'a'.");
});

it('remove() is terminal: the subtree leaves and cannot be removed twice', function () {
    $root = new RecordingScene('root');
    $a = new RecordingScene('a');
    $root->add($a);
    $root->remove($a);

    expect($root->children())->toBe([])
        ->and($a->parent())->toBeNull()
        ->and(fn () => $root->remove($a))->toThrow(TerranException::class, "'a' is not a child of 'root'.");
});

it('has no-op hooks by default', function () {
    $scene = new class('bare') extends \Terran\Scene {};
    $scene->ready();
    $scene->process(0.016);
    $scene->physicsProcess(1 / 60);
    $scene->exit();
    expect($scene->world())->toBeNull();
});
