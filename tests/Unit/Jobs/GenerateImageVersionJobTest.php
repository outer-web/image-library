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
use Spatie\Image\Enums\CropPosition;

function createSplitColorUploadedFile(int $width = 500, int $height = 1000): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'image-library-test-');

    $image = new Imagick();
    $image->newImage($width, $height, new ImagickPixel('red'));

    $draw = new ImagickDraw();
    $draw->setFillColor(new ImagickPixel('blue'));
    $draw->rectangle(0, (int) floor($height / 2), $width, $height);

    $image->drawImage($draw);
    $image->setImageFormat('png');
    $image->writeImage($path);
    $image->clear();
    $image->destroy();

    return new UploadedFile($path, 'example-image.png', 'image/png', null, true);
}

function getCenterPixelColor(string $path): array
{
    $image = new Imagick($path);

    $pixel = $image->getImagePixelColor(
        (int) floor($image->getImageWidth() / 2),
        (int) floor($image->getImageHeight() / 2)
    )->getColor();

    $image->clear();
    $image->destroy();

    return $pixel;
}

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

it('crops to the context aspect ratio using the default crop position if the context has none', function () {
    $user = User::factory()
        ->create();

    Config::set('image-library.defaults.crop_position', CropPosition::Top);

    $file = createSplitColorUploadedFile();

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

    $generatedImage = ImageLibrary::getSpatieImage()
        ->loadFile($image->getAbsolutePathForBreakpoint($breakpoint));

    $pixel = getCenterPixelColor($image->getAbsolutePathForBreakpoint($breakpoint));

    expect($generatedImage->getWidth())->toBe(300)
        ->and($generatedImage->getHeight())->toBe(300)
        ->and($pixel['r'])->toBeGreaterThan($pixel['b']);
});

it('prefers the context crop position over the default crop position if no crop_data is set', function () {
    $user = User::factory()
        ->create();

    Config::set('image-library.defaults.crop_position', CropPosition::Top);

    ImageLibrary::registerImageContext(
        ImageContext::make('bottom-crop-context')
            ->aspectRatio(AspectRatio::make(1, 1))
            ->cropPosition(CropPosition::Bottom)
            ->maxWidth([
                Breakpoint::Small->value => 300,
                Breakpoint::Medium->value => 600,
                Breakpoint::Large->value => 900,
                Breakpoint::ExtraLarge->value => 1200,
                Breakpoint::DoubleExtraLarge->value => 1500,
            ])
    );

    $file = createSplitColorUploadedFile();

    $sourceImage = SourceImage::upload($file);

    $image = Image::factory()
        ->forModel($user)
        ->create([
            'source_image_id' => $sourceImage->id,
            'context' => 'bottom-crop-context',
            'crop_data' => [],
        ]);

    $breakpoint = Breakpoint::Small;

    $job = new GenerateImageVersionJob($image->id, $breakpoint);

    $job->handle();

    Storage::disk($image->disk)
        ->assertExists($image->getRelativePathForBreakpoint($breakpoint));

    $pixel = getCenterPixelColor($image->getAbsolutePathForBreakpoint($breakpoint));

    expect($pixel['b'])->toBeGreaterThan($pixel['r']);
});

it('uses the largest possible crop before resizing when the source already matches the target aspect ratio', function () {
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

    $generatedImage = ImageLibrary::getSpatieImage()
        ->loadFile($image->getAbsolutePathForBreakpoint($breakpoint));

    expect($generatedImage->getWidth())->toBe(300)
        ->and($generatedImage->getHeight())->toBe(300);
});
