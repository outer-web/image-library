<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Outerweb\ImageLibrary\Entities\AspectRatio;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Enums\Breakpoint;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Jobs\GenerateImageVersionJob;
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

    $job = new GenerateImageVersionJob($image->id, Breakpoint::Small);

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

    $job = new GenerateImageVersionJob($image->id, Breakpoint::Small);

    expect($job->queue)->toBe(Config::string('image-library.queue.queue'));
});

it('generates an image per breakpoint', function () {
    $user = User::factory()
        ->create();

    $file = UploadedFile::fake()->image('example-image.jpg', 1000, 1000);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
        ]);

    $breakpoint = Breakpoint::Small;

    $job = new GenerateImageVersionJob($image->id, $breakpoint);

    $job->handle();

    Storage::disk($image->disk)
        ->assertExists($image->getRelativePathForBreakpoint($breakpoint));
});

it('can generate an image if the x and y crop coordinates are null', function () {
    $user = User::factory()
        ->create();

    $file = UploadedFile::fake()->image('example-image.jpg', 1000, 1000);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
            'crop_data' => [
                Breakpoint::Small->value => [
                    'width' => 500,
                    'height' => 500,
                    'x' => null,
                    'y' => null,
                ],
            ],
        ]);

    $breakpoint = Breakpoint::Small;

    $job = new GenerateImageVersionJob($image->id, $breakpoint);

    $job->handle();

    Storage::disk($image->disk)
        ->assertExists($image->getRelativePathForBreakpoint($breakpoint));
});

it('can generate an image if the x and y crop coordinates are set', function () {
    $user = User::factory()
        ->create();

    $file = UploadedFile::fake()->image('example-image.jpg', 1000, 1000);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
            'crop_data' => [
                Breakpoint::Small->value => [
                    'width' => 500,
                    'height' => 500,
                    'x' => 100,
                    'y' => 100,
                ],
            ],
        ]);

    $breakpoint = Breakpoint::Small;

    $job = new GenerateImageVersionJob($image->id, $breakpoint);

    $job->handle();

    Storage::disk($image->disk)
        ->assertExists($image->getRelativePathForBreakpoint($breakpoint));
});

it('can apply blur', function () {
    $user = User::factory()
        ->create();

    ImageLibrary::registerImageContext(
        ImageContext::make('blur-test-context')
            ->aspectRatio(AspectRatio::make(1, 1))
            ->blur(10)
    );

    $file = UploadedFile::fake()->image('example-image.jpg', 1000, 1000);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
            'context' => 'blur-test-context',
        ]);

    $breakpoint = Breakpoint::Small;

    $job = new GenerateImageVersionJob($image->id, $breakpoint);

    $job->handle();

    Storage::disk($image->disk)
        ->assertExists($image->getRelativePathForBreakpoint($breakpoint));
});

it('can apply greyscale', function () {
    $user = User::factory()
        ->create();

    ImageLibrary::registerImageContext(
        ImageContext::make('greyscale-test-context')
            ->aspectRatio(AspectRatio::make(1, 1))
            ->greyscale(true)
    );

    $file = UploadedFile::fake()->image('example-image.jpg', 1000, 1000);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
            'context' => 'greyscale-test-context',
        ]);

    $breakpoint = Breakpoint::Small;

    $job = new GenerateImageVersionJob($image->id, $breakpoint);

    $job->handle();

    Storage::disk($image->disk)
        ->assertExists($image->getRelativePathForBreakpoint($breakpoint));
});

it('can apply sepia', function () {
    $user = User::factory()
        ->create();

    ImageLibrary::registerImageContext(
        ImageContext::make('sepia-test-context')
            ->aspectRatio(AspectRatio::make(1, 1))
            ->sepia(true)
    );

    $file = UploadedFile::fake()->image('example-image.jpg', 1000, 1000);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
            'context' => 'sepia-test-context',
        ]);

    $breakpoint = Breakpoint::Small;

    $job = new GenerateImageVersionJob($image->id, $breakpoint);

    $job->handle();

    Storage::disk($image->disk)
        ->assertExists($image->getRelativePathForBreakpoint($breakpoint));
});

it('applies default cropping if no crop_data is set', function () {
    $user = User::factory()
        ->create();

    $file = UploadedFile::fake()->image('example-image.jpg', 1000, 1000);

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
            'context' => 'context-single',
            'crop_data' => [],
        ]);

    $breakpoint = Breakpoint::Small;

    $job = new GenerateImageVersionJob($image->id, $breakpoint);

    $job->handle();

    Storage::disk($image->disk)
        ->assertExists($image->getRelativePathForBreakpoint($breakpoint));
});
