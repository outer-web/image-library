<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\SourceImage;
use Outerweb\ImageLibrary\Tests\Fixtures\Models\User;

test('a model using the trait has the images relation', function () {
    $user = User::factory()
        ->create();

    expect($user->images())
        ->toBeInstanceOf(MorphMany::class);

    Image::factory()
        ->forModel($user)
        ->create();

    expect($user->images)
        ->toHaveCount(1)
        ->each->toBeInstanceOf(Image::class);
});

test('a model using the trait must include a context when attaching an image', function () {
    $user = User::factory()
        ->create();

    $sourceImage = SourceImage::factory()
        ->create();

    $user->attachImage($sourceImage);
})->throws(InvalidArgumentException::class);

test('a model using the trait has the attachImage method', function () {
    $user = User::factory()
        ->create();

    $sourceImage = SourceImage::factory()
        ->create();

    $context = ImageLibrary::getImageContextByKey('context-single');

    $image = $user->attachImage($sourceImage, ['context' => $context]);

    expect($image)
        ->toBeInstanceOf(Image::class)
        ->and($image->model_type)->toBe($user->getMorphClass())
        ->and($image->model_id)->toBe($user->getKey())
        ->and($image->source_image_id)->toBe($sourceImage->id)
        ->and($image->disk)->toBe($sourceImage->disk)
        ->and($image->context->getKey())->toEqual($context->getKey());

    expect($user->images)
        ->toHaveCount(1)
        ->first()->id->toBe($image->id);
});

test('a model using the trait can use an ImageContext when attaching an image', function () {
    $user = User::factory()
        ->create();

    $sourceImage = SourceImage::factory()
        ->create();

    $context = ImageLibrary::getImageContextByKey('context-single');

    $image = $user->attachImage($sourceImage, ['context' => $context]);

    expect($image)
        ->toBeInstanceOf(Image::class)
        ->and($image->context->getKey())->toEqual($context->getKey());
});

test('a model using the trait can use an ImageContext key when attaching an image', function () {
    $user = User::factory()
        ->create();

    $sourceImage = SourceImage::factory()
        ->create();

    $contextKey = 'context-single';

    $image = $user->attachImage($sourceImage, ['context' => $contextKey]);

    expect($image)
        ->toBeInstanceOf(Image::class)
        ->and($image->context->getKey())->toBe($contextKey);
});

test('a model using the trait can attach an image to a custom MorphOne relation', function () {
    $user = User::factory()
        ->create();

    $sourceImage1 = SourceImage::factory()
        ->create();

    $sourceImage2 = SourceImage::factory()
        ->create();

    $context = ImageLibrary::getImageContextByKey('context-single');

    $profilePicture1 = $user->attachImage($sourceImage1, ['context' => $context], 'profilePicture');

    expect($profilePicture1)
        ->toBeInstanceOf(Image::class);

    expect($user->profilePicture)
        ->id->toBe($profilePicture1->id);

    $profilePicture2 = $user->attachImage($sourceImage2, ['context' => $context], 'profilePicture');

    expect($profilePicture2)
        ->toBeInstanceOf(Image::class)
        ->and($profilePicture2->id)->not->toBe($profilePicture1->id);

    expect($user->profilePicture)
        ->id->toBe($profilePicture2->id);

    expect(Image::where('model_type', $user->getMorphClass())
        ->where('model_id', $user->getKey())
        ->count())->toBe(1);
});

test('a model using the trait can attach an image to a custom MorphMany relation', function () {
    $user = User::factory()
        ->create();

    $sourceImage1 = SourceImage::factory()
        ->create();

    $sourceImage2 = SourceImage::factory()
        ->create();

    $context = ImageLibrary::getImageContextByKey('context-multiple');

    $galleryImage1 = $user->attachImage($sourceImage1, ['context' => $context], 'gallery');

    expect($galleryImage1)
        ->toBeInstanceOf(Image::class);

    expect($user->gallery)
        ->toHaveCount(1)
        ->first()->id->toBe($galleryImage1->id);

    $galleryImage2 = $user->attachImage($sourceImage2, ['context' => $context], 'gallery');

    expect($galleryImage2)
        ->toBeInstanceOf(Image::class)
        ->and($galleryImage2->id)->not->toBe($galleryImage1->id);

    expect($user->gallery)
        ->toHaveCount(2)
        ->pluck('id')->toContain($galleryImage1->id)
        ->and($user->gallery)
        ->pluck('id')->toContain($galleryImage2->id);
});

test('attaching an image to an invalid relation throws an exception', function () {
    $user = User::factory()
        ->create();

    $sourceImage = SourceImage::factory()
        ->create();

    $context = ImageLibrary::getImageContextByKey('context-single');

    $user->attachImage($sourceImage, ['context' => $context], 'invalidRelation');
})->throws(InvalidArgumentException::class, 'Relation invalidRelation does not exist on the model.');

test('attaching an image to a relation that is not MorphOne or MorphMany throws an exception', function () {
    $user = User::factory()
        ->create();

    $sourceImage = SourceImage::factory()
        ->create();

    $context = ImageLibrary::getImageContextByKey('context-single');

    $user->attachImage($sourceImage, ['context' => $context], 'friends');
})->throws(InvalidArgumentException::class, 'Relation friends is not a valid MorphOne or MorphMany relation.');

test('a model using the trait can attach an image with custom properties', function () {
    $user = User::factory()
        ->create();

    $sourceImage = SourceImage::factory()
        ->create();

    $context = ImageLibrary::getImageContextByKey('context-single');

    $customProperties = [
        'caption' => 'A custom caption',
    ];

    $image = $user->attachImage($sourceImage, [
        'context' => $context,
        'custom_properties' => $customProperties,
    ]);

    expect($image)
        ->toBeInstanceOf(Image::class)
        ->and($image->custom_properties)->toEqual($customProperties);
});

test('attaching two images to a single-image context removes the previous image', function () {
    $user = User::factory()
        ->create();

    $sourceImage1 = SourceImage::factory()
        ->create();

    $sourceImage2 = SourceImage::factory()
        ->create();

    $context = ImageLibrary::getImageContextByKey('context-single');

    $image1 = $user->attachImage($sourceImage1, ['context' => $context]);

    expect($user->images)
        ->toHaveCount(1)
        ->first()->id->toBe($image1->id);

    $image2 = $user->attachImage($sourceImage2, ['context' => $context]);

    expect($user->images)
        ->toHaveCount(1)
        ->first()->id->toBe($image2->id)
        ->and($image2->id)->not->toBe($image1->id);
});

test('attaching two images to a multiple-image context keeps both images', function () {
    $user = User::factory()
        ->create();

    $sourceImage1 = SourceImage::factory()
        ->create();

    $sourceImage2 = SourceImage::factory()
        ->create();

    $context = ImageLibrary::getImageContextByKey('context-multiple');

    $image1 = $user->attachImage($sourceImage1, ['context' => $context]);

    expect($user->images)
        ->toHaveCount(1)
        ->first()->id->toBe($image1->id);

    $image2 = $user->attachImage($sourceImage2, ['context' => $context]);

    expect($user->images)
        ->toHaveCount(2)
        ->pluck('id')->toContain($image1->id)
        ->and($user->images)
        ->pluck('id')->toContain($image2->id);
});
