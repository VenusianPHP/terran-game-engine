<?php
declare(strict_types=1);

use Terran\TerranException;
use Voyager\Contracts\Core\VenusianFrameworkException;

it('has an exception of its own under the framework root', function () {
    expect(new TerranException('x'))->toBeInstanceOf(VenusianFrameworkException::class);
});

it('fakes an RGBA8 target that counts presents', function () {
    $target = fakeTarget(8, 4);
    expect($target->pixelSize())->toBe([8, 4])
        ->and($target->framebuffer()->viewportWidth())->toBe(8)
        ->and($target->present()->presented)->toBe(1);
});

it('mints Velvet over a fake target', function () {
    $ge = drawing()->renderer('velvet', ['output' => fakeTarget()]);
    expect($ge->name())->toBe('velvet')->and($ge->width())->toBe(32);
});
