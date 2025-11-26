<?php

declare(strict_types=1);

use Outerweb\ImageLibrary\Entities\AspectRatio;

it('has a make method', function () {
    $aspectRatio = AspectRatio::make(4, 3);

    expect($aspectRatio)
        ->toBeInstanceOf(AspectRatio::class)
        ->horizontal->toBe(4)
        ->vertical->toBe(3);
});

it('can be converted to string', function () {
    $aspectRatio = new AspectRatio(4, 3);

    expect((string) $aspectRatio)
        ->toBe('4:3');

    expect($aspectRatio->toString())
        ->toBe('4:3');
});

it('can be converted to array', function () {
    $aspectRatio = new AspectRatio(16, 9);

    expect($aspectRatio->toArray())
        ->toBe([
            'horizontal' => 16,
            'vertical' => 9,
        ]);
});
