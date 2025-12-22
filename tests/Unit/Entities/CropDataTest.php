<?php

declare(strict_types=1);

use Outerweb\ImageLibrary\Entities\CropData;

it('has a make method', function () {
    $cropData = CropData::make(100, 100, 10, 20, 30, -1, 1);

    expect($cropData)
        ->toBeInstanceOf(CropData::class)
        ->width->toBe(100)
        ->height->toBe(100)
        ->x->toBe(10)
        ->y->toBe(20)
        ->rotate->toBe(30)
        ->scaleX->toBe(-1)
        ->scaleY->toBe(1);
});

it('can be converted to array', function () {
    $cropData = CropData::make(100, 100, 10, 20, 30, -1, 1);

    expect($cropData->toArray())
        ->toBe([
            'width' => 100,
            'height' => 100,
            'x' => 10,
            'y' => 20,
            'rotate' => 30,
            'scaleX' => -1,
            'scaleY' => 1,
        ]);
});
