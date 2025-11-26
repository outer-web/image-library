<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Outerweb\ImageLibrary\Tests\TestCase;

uses(TestCase::class)
    ->in(__DIR__)
    ->beforeEach(function () {
        Storage::fake('public');
        Bus::fake();
    });
