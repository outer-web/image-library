# Image Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/outerweb/image-library.svg?style=flat-square)](https://packagist.org/packages/outerweb/image-library)
[![Total Downloads](https://img.shields.io/packagist/dt/outerweb/image-library.svg?style=flat-square)](https://packagist.org/packages/outerweb/image-library)

This package adds ways to store and link images to your models.

It provides:

- A way to store images on different disks
- An Image and ImageConversion model to interact with the images and image_conversions table
- A way to map cropper.js data to the image_conversions table which than automatically generates and stores the conversion image on disk
- A way to define conversions for images (e.g. thumbnail, 16:9, ...). Using spatie/image you can crop and add effects to the images.
- Support for webp images (automatically generated and stored on disk and rendered using the picture HTML element)
- Support for responsive images (automatically generated and stored on disk and rendered using the srcset attribute)
- A way to render images using the picture HTML element
- A way to render images using the image HTML element

## Installation

You can install the package via composer:

```bash
composer require outerweb/image-library
```

Run the install command:

```bash
php artisan image-library:install
```

Add the `<x-image-library-scripts />` blade component to your layout (at the bottom of the body tag).

```blade
<x-image-library-scripts />
```

This will add a script tag to the bottom of the body tag that will dynamically set the image width as the sizes attribute of the image tag. This is an automatic way of letting the browser know which responsive image variant to download based on the device's screen size, resolution, density and supported image formats.

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
        ->label('Thumbnail')
        ->translateLabel()
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

```php
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;

ConversionDefinition::make()
    ->name('thumbnail');
```

#### Label (optional)

The label of the conversion. This is can be used by other packages that depend on this package to show the label in the user interface. E.g. in our Filament Image Library package, this is used to display the conversion name above the cropper.

```php
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;

ConversionDefinition::make()
    ->label('Thumbnail');
```

#### Translate label (optional)

Whether the label should be translated. By default, the label will not be translated. This method will take the value of the label and put it through the `__()` function.

```php
use OuterWeb\ImageLibrary\Entities\ConversionDefinition;

ConversionDefinition::make()
    ->label('conversions.labels.thumbnail');
    ->translateLabel();
```

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

You can render images by using the `<x-image-library-image />` blade component.

```blade
<x-image-library-image :image="$image" conversion="thumbnail" />
```

This will render a responsive image with the `thumbnail` conversion.

You can also render a `<x-image-library-picture />` blade component.

```blade
<x-image-library-picture :image="$image" conversion="thumbnail" />
```

This gives the browser the ability to choose the best image to download based on the device's screen size, resolution, density and supported image formats.

#### Fallback image

You can provide a fallback image by using the `fallback` attribute.

```blade
<x-image-library-image :image="$image" conversion="thumbnail" fallback="fallback-image.jpg" />
```

This can be a string or another `Image` model.

The fallback image will be rendered using the conversion defined in the `conversion` attribute.

#### Fallback conversion

You can provide a fallback conversion by using the `fallback-conversion` attribute.

```blade
<x-image-library-image :image="$image" conversion="thumbnail" fallback-conversion="original" />
```

Combining this with the `fallback` attribute, you can have different outcomes when the image and/or conversion are not available:

- Defining a `fallback` attribute and a `fallback-conversion` attribute will render the fallback image using the fallback conversion if the fallback image is an `Image` model.
- Only defining a `fallback` attribute will render the fallback image using the fallback conversion if the fallback image is an `Image` model.
- Only defining a `fallback-conversion` attribute will render the image using the fallback conversion.

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

## Upgrading

Please see [UPGRADING](UPGRADING.md) for information on upgrading to a new major version.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Simon Broekaert](https://github.com/SimonBroekaert)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

```

```
