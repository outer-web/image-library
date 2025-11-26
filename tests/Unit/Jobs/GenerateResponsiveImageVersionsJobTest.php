<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Outerweb\ImageLibrary\Enums\Breakpoint;
use Outerweb\ImageLibrary\Jobs\GenerateImageVersionJob;
use Outerweb\ImageLibrary\Jobs\GenerateResponsiveImageVersionsJob;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\SourceImage;
use Outerweb\ImageLibrary\Tests\Fixtures\Models\User;

it('is dispatched on the correct connection and queue', function () {
    $user = User::factory()
        ->create();

    $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
        ]);

    new GenerateImageVersionJob($image->id, Breakpoint::Small)->handle();

    $job = new GenerateResponsiveImageVersionsJob($image->id, Breakpoint::Small);

    expect($job->connection)->toBe(Config::string('image-library.queue.connection'));
});

it('is dispatched on the correction queue', function () {
    $user = User::factory()
        ->create();

    $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
        ]);

    new GenerateImageVersionJob($image->id, Breakpoint::Small)->handle();

    $job = new GenerateResponsiveImageVersionsJob($image->id, Breakpoint::Small);

    expect($job->queue)->toBe(Config::string('image-library.queue.queue'));
});

it('can generate multiple responsive image versions', function () {
    $user = User::factory()
        ->create();

    $file = UploadedFile::fake()->image('example-image.jpg', 1920, 1080);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
            'context' => 'context-single',
        ]);

    new GenerateImageVersionJob($image->id, Breakpoint::ExtraExtraLarge)->handle();

    new GenerateResponsiveImageVersionsJob($image->id, Breakpoint::ExtraExtraLarge)
        ->handle();

    expect($image->getResponsiveRelativePathsForBreakpoint(Breakpoint::ExtraExtraLarge))
        ->toBeInstanceOf(Collection::class);

    expect($image->getResponsiveRelativePathsForBreakpoint(Breakpoint::ExtraExtraLarge)->count())
        ->toBeGreaterThan(0);
});
