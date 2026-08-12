<?php

use ScrapyardIO\GameEngine\Resources\ImageTexture;
use ScrapyardIO\GameEngine\Resources\ResourceStore;

it('round-trips textures through sqlite', function (): void {
    $path = sys_get_temp_dir().'/ge-resource-'.uniqid('', true).'.sqlite';
    $store = new ResourceStore($path);
    $tex = ImageTexture::solid(8, 8, 0xFF00FF00);

    $store->putTexture('icons/green', $tex);

    expect($store->has('icons/green'))->toBeTrue();

    $loaded = $store->getTexture('icons/green');

    expect($loaded)->not->toBeNull()
        ->and($loaded->width())->toBe(8)
        ->and($loaded->height())->toBe(8);

    @unlink($path);
});
