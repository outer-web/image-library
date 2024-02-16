# Image Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/outerweb/image-library.svg?style=flat-square)](https://packagist.org/packages/outerweb/image-library)
[![Total Downloads](https://img.shields.io/packagist/dt/outerweb/image-library.svg?style=flat-square)](https://packagist.org/packages/outerweb/image-library)

This package adds ways to store and link images to your models.

## Installation

You can install the package via composer:

```bash
composer require outerweb/image-library
```

Publish the migration files:

```bash
php artisan vendor:publish --provider="OuterWeb\ImageLibrary\ImageLibraryServiceProvider" --tag="image-library-migrations"
```

Publish the config file:

```bash
php artisan vendor:publish --provider="OuterWeb\ImageLibrary\ImageLibraryServiceProvider" --tag="image-library-config"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

You can configure the package by editing the `config/image-library.php` file.

Each setting is documented in the config file itself.

## Usage

### Defining conversions

You can define conversions anywhere in your application. We recommend doing this in a service provider.
To do this, you can use the `ConversionDefinition` entity.

```php
use OuterWeb\ImageLibrary\Facades\ImageLibrary;
use OuterWeb\ImageLibrary\Entities\AspectRatio;
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;
use OuterWeb\ImageLibrary\Entities\Effects;

ImageLibrary::addConversionDefinition(
    ConversionDefinition::make()
        ->name('thumbnail')
        ->aspectRatio(
            AspectRatio::make()
                ->x(1)
                ->y(1)
        )
        ->defaultWidth(100)
        ->defaultHeight(100)
        ->effects(
            Effects::make()
                ->blur(10)
                ->pixelate(10)
                ->greyscale()
                ->sepia()
                ->sharpen(10)
        )
);
```

#### Name (required)

The name of the conversion. This is the name you will use to refer to the conversion.

#### Aspect ratio (required)

The aspect ratio of the conversion. You can define this by using the `AspectRatio` entity.

```php
use OuterWeb\ImageLibrary\Entities\AspectRatio;
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;

ConversionDefinition::make()
    ->aspectRatio(
        AspectRatio::make()
            ->x(1)
            ->y(1)
    );
```

Or by providing a string.

```php
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;

ConversionDefinition::make()
    ->aspectRatio('16:9');
```

Or by providing an array.

```php
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;

ConversionDefinition::make()
    ->aspectRatio([16, 9]);
```

#### Default width and height (optional)

The default width and height of the conversion. This is the size the image will be cropped to by default.
These values are overridden by the width and height saved in the database.

```php
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;

ConversionDefinition::make()
    ->defaultWidth(100)
    ->defaultHeight(100);
```

#### Effects (optional)

You can apply effects to the conversion. You can define this by using the `Effects` entity.

```php
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;
use OuterWeb\ImageLibrary\Entities\Effects;

ConversionDefinition::make()
    ->effects(
        Effects::make()
            ->blur(10)
            ->pixelate(10)
            ->greyscale()
            ->sepia()
            ->sharpen(10)
    );
```

Or by providing an array.

```php
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;

ConversionDefinition::make()
    ->effects([
        'blur' => 10,
        'pixelate' => 10,
        'greyscale' => true,
        'sepia' => true,
        'sharpen' => 10
    ]);
```

### Uploading images

You can upload images to the library by using the `ImageLibrary` facade.

```php
use OuterWeb\ImageLibrary\Facades\ImageLibrary;

$image = ImageLibrary::upload($request->file('image'));
```

By default, the image will be stored in the `public` disk. You can change this by setting the `default_disk` option in the config file or by passing it as the second argument to the `upload` method.

```php
use OuterWeb\ImageLibrary\Facades\ImageLibrary;

$image = ImageLibrary::upload($request->file('image'), 's3');
```

You may also pass a title and alt text in the third argument.

```php
use OuterWeb\ImageLibrary\Facades\ImageLibrary;

$image = ImageLibrary::upload($request->file('image'), 's3', [
    'title' => 'My image',
    'alt' => 'This is my image'
]);
```

If you want those attributes to be translatable, we have directly integrated Spatie's `laravel-translatable` package. To enable this, you need to set the `spatie_translatable` option to `true` in the config file. After that, you can pass the translations in the third argument.

```php
use OuterWeb\ImageLibrary\Facades\ImageLibrary;

$image = ImageLibrary::upload($request->file('image'), 's3', [
    'title' => [
        'en' => 'My image',
        'nl' => 'Mijn afbeelding'
    ],
    'alt' => [
        'en' => 'This is my image',
        'nl' => 'Dit is mijn afbeelding'
    ]
]);
```

When an image is uploaded, these things will happen:

1. The image will be stored on the specified disk.
2. If webp support is enabled, a webp version of the image will be generated and stored on the specified disk.
3. If responsive image support is enabled, responsive images will be generated and stored on the specified disk.
4. If webp support is enabled, a webp version of each responsive image will be generated and stored on the specified disk.
5. A record will be created in the `images` table. You can use the Image model to interact with this record.
6. For each defined Conversion, a record will be created in the `image_conversions` table.
7. The conversion images will be generated and stored on the specified disk.
8. If webp support is enabled, a webp version of the image conversion will be generated and stored on the specified disk.
9. If responsive image support is enabled, responsive images for each conversion will be generated and stored on the specified disk.
10. If webp support is enabled, a webp version of each responsive image for each conversion will be generated and stored on the specified disk.

### Rendering images

You can render images by using the `<x-image />` blade component.

```blade
<x-image :image="$image" conversion="thumbnail" />
```

This will render a responsive image with the `thumbnail` conversion.

You can also render a `<x-picture />` blade component.

```blade
<x-picture :image="$image" conversion="thumbnail" />
```

This gives the browser the ability to choose the best image to download based on the device's screen size, resolution, density and supported image formats.

#### Fallback image

You can provide a fallback image by using the `fallback` attribute.

```blade
<x-image :image="$image" conversion="thumbnail" fallback="fallback-image.jpg" />
```

This can be a string or another `Image` model.

The fallback image will be rendered using the conversion defined in the `conversion` attribute.

#### Fallback conversion

You can provide a fallback conversion by using the `fallback-conversion` attribute.

```blade
<x-image :image="$image" conversion="thumbnail" fallback-conversion="original" />
```

If you supply a fallback conversion with the `fallback` attribute, the conversion of the `image` attribute image will be used as the fallback image.

If you supply a `fallback` attribute and no `fallback-conversion` attribute, the fallback image will be rendered using the fallback conversion.

### Linking images to models

You may link images to your models by any means you like. The package does not provide a way to do this.

You are free to create:

- A polymorphic relationship
- A many-to-many relationship
- A one-to-many relationship
- ...

### The image model

The package provides an `Image` model that you can use to interact with the images table.

You may change the model by setting the `models.image` option in the config file.

It saves the following data in the database:

- `id` : The primary key
- `uuid` : Used as directory name when storing the image on disk
- `disk` : The disk on which the image is stored
- `mime_type` : The mime type of the image
- `file_extension` : The file extension of the image
- `width` : The width of the image in pixels
- `height` : The height of the image in pixels
- `size` : The size of the image in bytes
- `title` : The title of the image (stored as json to optionally support translations)
- `alt` : The alt text of the image (stored as json to optionally support translations)
- `created_at` : The creation date
- `updated_at` : The last update date

### The image conversions model

The package provides an `ImageConversion` model that you can use to interact with the image_conversions table.

You may change the model by setting the `models.image_conversion` option in the config file.

It saves the following data in the database:

- `id` : The primary key
- `image_id` : The id of the image
- `conversion_name` : The name of the conversion
- `conversion_md5` : The md5 hash of the conversion to check if a conversion images needs to be re-generated after changing the ConversionDefinition
- `width` : The width of the conversion image in pixels
- `height` : The height of the conversion image in pixels
- `size` : The size of the conversion image in bytes
- `x` : The x coordinate to crop the image at
- `y` : The y coordinate to crop the image at
- `rotate` : The rotation of the image in degrees (0, 90, 180, 270)
- `scale_x` : The x scale of the image
- `scale_y` : The y scale of the image
- `created_at` : The creation date
- `updated_at` : The last update date

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Simon Broekaert](https://github.com/SimonBroekaert)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

```

```
