<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Enums\Breakpoint;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\SourceImage;
use Spatie\Image\Image as SpatieImage;

it('has a method to return the breakpoint enum class', function () {
    $enumClass = ImageLibrary::getBreakpointEnum();

    expect($enumClass)
        ->toBeString();

    expect(class_exists($enumClass))
        ->toBeTrue();

    expect($enumClass)
        ->toEqual(Breakpoint::class);
});

it('has a method to return the image model class', function () {
    $modelClass = ImageLibrary::getImageModel();

    expect($modelClass)
        ->toBeString();

    expect(class_exists($modelClass))
        ->toBeTrue();

    expect($modelClass)
        ->toEqual(Image::class);
});

it('has a method to return the source image model class', function () {
    $modelClass = ImageLibrary::getSourceImageModel();

    expect($modelClass)
        ->toBeString();

    expect(class_exists($modelClass))
        ->toBeTrue();

    expect($modelClass)
        ->toEqual(SourceImage::class);
});

it('has a method to return a Spatie Image instance', function () {
    $spatieImage = ImageLibrary::getSpatieImage();

    expect($spatieImage)
        ->toBeInstanceOf(SpatieImage::class);
});

it('can register one or more image contexts', function () {
    ImageLibrary::registerImageContexts([
        ImageContext::make('context-single'),
        ImageContext::make('context-multiple'),
    ]);

    expect(count(ImageLibrary::getImageContexts()))
        ->toEqual(2);
});

it('throws an exception when registering invalid image contexts', function () {
    ImageLibrary::registerImageContexts([
        ImageContext::make('context-single'),
        'invalid-context',
    ]);
})->throws(InvalidArgumentException::class, 'Expected instance of ImageContext, but got string instead.');

it('can register a single image context', function () {
    ImageLibrary::registerImageContext(
        ImageContext::make('context-single')
    );
    ImageLibrary::registerImageContext(
        ImageContext::make('context-multiple')
    );

    expect(count(ImageLibrary::getImageContexts()))
        ->toEqual(2);
});

it('can remove an image context', function () {
    $imageContext = ImageContext::make('context-single');
    $imageContext = ImageContext::make('context-multiple');

    ImageLibrary::registerImageContext($imageContext);

    expect(count(ImageLibrary::getImageContexts()))
        ->toEqual(2);

    ImageLibrary::removeImageContext($imageContext);

    expect(count(ImageLibrary::getImageContexts()))
        ->toEqual(1);
});

it('can get all registered image contexts', function () {
    ImageLibrary::registerImageContexts([
        ImageContext::make('context-single'),
        ImageContext::make('context-multiple'),
    ]);

    $imageContexts = ImageLibrary::getImageContexts();

    expect($imageContexts)
        ->toBeArray()
        ->toHaveCount(2);
});

it('can get an image context by its key', function () {
    $imageContext = ImageContext::make('context-single');

    ImageLibrary::registerImageContext($imageContext);

    $fetchedContext = ImageLibrary::getImageContextByKey('context-single');

    expect($fetchedContext)
        ->toBeInstanceOf(ImageContext::class)
        ->and($fetchedContext?->getKey())
        ->toEqual('context-single');
});

it('returns null when getting an image context by a non-existent key', function () {
    $fetchedContext = ImageLibrary::getImageContextByKey('non-existent-key');

    expect($fetchedContext)
        ->toBeNull();
});

it('returns null when getting an image context by a blank key', function () {
    expect(ImageLibrary::getImageContextByKey(''))
        ->toBeNull();

    expect(ImageLibrary::getImageContextByKey(null))
        ->toBeNull();
});

it('has a method to upload a file as a source image', function () {
    $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

    $sourceImage = ImageLibrary::upload($file);

    expect($sourceImage)
        ->toBeInstanceOf(SourceImage::class)
        ->and($sourceImage->disk)
        ->toEqual('public')
        ->and($sourceImage->name)
        ->toEqual('example-image')
        ->and($sourceImage->extension)
        ->toEqual('jpg')
        ->and($sourceImage->mime_type)
        ->toEqual('image/jpeg')
        ->and($sourceImage->width)
        ->toEqual(10)
        ->and($sourceImage->height)
        ->toEqual(10);

    Storage::disk($sourceImage->disk)
        ->assertExists($sourceImage->getRelativePath());
});

it('has a method to determine if temporary URLs should be used for a given disk', function () {
    $usesTemporaryUrls = ImageLibrary::shouldUseTemporaryUrlsForDisk('s3');

    expect($usesTemporaryUrls)
        ->toBeBool();
});

it('returns the default value when determining if temporary URLs should be used for a disk not explicitly configured', function () {
    $usesTemporaryUrls = ImageLibrary::shouldUseTemporaryUrlsForDisk('non-existent-disk');

    expect($usesTemporaryUrls)
        ->toEqual(false);
});

it('has a method to determine the temporary URL expiration time for a given disk', function () {
    $expirationTime = ImageLibrary::getTemporaryUrlExpirationMinutesForDisk('s3');

    expect($expirationTime)
        ->toBeInt();
});

it('returns the default value when determining the temporary URL expiration time for a disk not explicitly configured', function () {
    $expirationTime = ImageLibrary::getTemporaryUrlExpirationMinutesForDisk('non-existent-disk');

    expect($expirationTime)
        ->toBeInt();
});
