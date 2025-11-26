# Image Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/outerweb/image-library.svg?style=flat-square)](https://packagist.org/packages/outerweb/image-library)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/outerweb/image-library/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/outer-web/image-library/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/outerweb/image-library/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/outer-web/image-library/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/outerweb/image-library.svg?style=flat-square)](https://packagist.org/packages/outerweb/image-library)

A powerful Laravel package for managing images with responsive breakpoints, automatic optimization, and contextual configurations. Store and link images to your models with advanced features like automatic WebP generation, responsive image versions, and flexible image contexts.

> ⚠️ **Caution:** V3 is a complete rewrite of the package and logic. Please take a look at the [upgrade guide](./docs/upgrade-to-v3.md) before upgrading from v2.x to v3.x.

## Table of Contents

-   [Requirements](#requirements)
-   [Installation](#installation)
-   [Core concepts](#core-concepts)
    -   [SourceImages](#sourceimages)
    -   [Images](#images)
    -   [ImageContexts](#imagecontexts)
    -   [Breakpoints](#breakpoints)
-   [Configuration](#configuration)
    -   [The config file](#the-config-file)
    -   [Javascript](#javascript)
    -   [Defining ImageContexts](#defining-imagecontexts)
    -   [Custom Breakpoints](#custom-breakpoints)
-   [Usage](#usage)
    -   [Uploading an image](#uploading-an-image)
    -   [Attaching an image to your model](#attaching-an-image-to-your-model)
    -   [Using your model image(s)](#using-your-model-images)
    -   [Rendering images](#rendering-images)
-   [Upgrading](#upgrading)
-   [Changelog](#changelog)
-   [License](#license)

## Requirements

This package uses [spatie/image](https://github.com/spatie/image) for image manipulations, so it requires the GD or Imagick PHP extension.

## Installation

You can install the package via composer:

```bash
composer require outerweb/image-library
```

Run the install command to publish the migrations, config file, and service provider:

```bash
php artisan image-library:install
```

This will:

-   Publish the configuration file to `config/image-library.php`
-   Copy and register the `ImageLibraryServiceProvider` in your application
-   Publish the database migrations
-   Optionally run the migrations

## Core concepts

### SourceImages

SourceImages are the original images uploaded to the system. They are not directly linked to any model. They are meant for internal use in this package.

### Images

Images are the link between a SourceImage and your Model(s). The Image has a `BelongsTo` relationship to the SourceImage and a `MorphTo` relationship to your Model.

You can see these as an instance of the uploaded images in a specific use case. You can define the use case using the `context` attribute on the Image model.

### ImageContexts

ImageContexts allow you to define a configuration for images used in a specific way. Examples include "profile_picture", "thumbnail", "gallery_entry", "hero", etc.

They are fully customizable and should be defined in the ImageServiceProvider or in a custom service provider.

Images get generated based on the defined ImageContext when the Image model gets created or updated. This is based on the image_context_hash that is stored per Image. It is a hashed version of the whole configuration so that changes in the context will trigger regeneration of the images.

#### WebP versions

This package also generates WebP versions of images for better performance in modern browsers. You can configure whether to generate WebP versions globally in the config file or per ImageContext.

#### Responsive versions

The package supports generating multiple responsive versions of images based on defined breakpoints. You can configure the sizes and aspect ratios for each breakpoint in the ImageContext, ensuring optimal display across different devices.

Each breakpoint can have a minimum and maximum width defined in the context. This allows the package to generate only necessary image sizes based on your design requirements.

### Breakpoints

Breakpoints define responsive screen sizes for image optimization. The package uses a Breakpoint enum that follows Tailwind CSS conventions, allowing you to specify different image configurations for various screen sizes.

Available breakpoints:

-   **`Breakpoint::Small`** (`'sm'`): 640px and up - Mobile devices in landscape, small tablets
-   **`Breakpoint::Medium`** (`'md'`): 768px and up - Tablets in portrait mode
-   **`Breakpoint::Large`** (`'lg'`): 1024px and up - Tablets in landscape, small desktops
-   **`Breakpoint::ExtraLarge`** (`'xl'`): 1280px and up - Desktop screens
-   **`Breakpoint::ExtraExtraLarge`** (`'2xl'`): 1536px and up - Large desktop screens

You can use these breakpoints to define different aspect ratios, sizes, crop positions, and effects for different screen sizes, ensuring optimal image display across all devices.

> **Note:** If the default breakpoints don't match your design system, you can create custom breakpoints. See [Custom Breakpoints](#custom-breakpoints) in the Configuration section.

## Configuration

### The config file

The config file allows you to customize various aspects of the image library. Some key configuration options include:

-   **`defaults.disk`**: The default filesystem disk for storing images if not specified during upload
-   **`generate.webp`**: Automatically generate WebP versions of images if not specified in the image context
-   **`generate.responsive_versions`**: Generate multiple sizes for responsive images if not specified in the image context
-   **`defaults.crop_position`**: Default crop position for image transformations if not specified in the image context
-   **`models`**: Customize the Eloquent models used by the package to easily extend functionality
-   **`spatie_image.driver`**: Choose between 'gd' or 'imagick' for image manipulations

### Javascript

The package includes a JavaScript component that automatically sets the `sizes` attribute on `picture` elements rendered by the package. This ensures that the browser selects the most appropriate image size based on the actual display size of the image.

To include the script, add the following Blade component to the `<head>` section of your layout:

```blade
<x-image-library::scripts />
```

### Defining ImageContexts

ImageContexts are defined in your application's `ImageLibraryServiceProvider` that gets published during installation. This provider extends the base service provider and allows you to define contexts in the `imageContexts()` method:

```php
<?php

namespace App\Providers;

use Outerweb\ImageLibrary\Entities\AspectRatio;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Enums\Breakpoint;
use Outerweb\ImageLibrary\Providers\ImageLibraryServiceProvider as BaseServiceProvider;
use Override;

class ImageLibraryServiceProvider extends BaseServiceProvider
{
    #[Override]
    public function imageContexts(): array
    {
        return [
            // Profile picture - square aspect ratio, single image
            ImageContext::make('profile_picture')
                ->label(fn (): string => __('Profile Picture'))
                ->aspectRatio(AspectRatio::make(1, 1))
                ->allowsMultiple(false),

            // Gallery images - 16:9 aspect ratio, multiple images allowed
            ImageContext::make('gallery')
                ->label(fn (): string => __('Gallery'))
                ->aspectRatio(AspectRatio::make(16, 9))
                ->allowsMultiple(true),

            // Thumbnail - square with responsive sizing
            ImageContext::make('thumbnail')
                ->label(fn (): string => __('Thumbnail'))
                ->aspectRatio(AspectRatio::make(1, 1))
                ->maxWidth([
                    Breakpoint::Small->value => 150,
                    Breakpoint::Medium->value => 200,
                    Breakpoint::Large->value => 250,
                ])
                ->allowsMultiple(false),
        ];
    }
}
```

### Configuration Methods

ImageContexts provide extensive configuration options for different responsive breakpoints and image processing needs:

#### Label

You can define a human-readable label for each context to use in your UI.

```
ImageContext::make('thumbnail')
    ->label('Thumbnail')
```

If you need localization, you can define the label using a closure:

```php
ImageContext::make('thumbnail')
    ->label(fn() => __('Thumbnail'))
```

If you need information about the ImageContext in the label, you can use the provided `ImageContext` instance:

```php
ImageContext::make('thumbnail')
    ->label(fn(ImageContext $context) => __('Image Context: :context', ['context' => $context->key]))
```

#### Allowing multiple images

You can specify whether multiple images are allowed in this context:

```php
ImageContext::make('gallery')
    ->allowsMultiple(true);
```

#### Generating WebP versions

By default, WebP versions are generated based on the global config. You can override this per context:

```php
ImageContext::make('thumbnail')
    ->generateWebP(false);
```

#### Generating responsive versions

By default, responsive versions are generated based on the global config. You can override this per context:

```php
ImageContext::make('thumbnail')
    ->generateResponsiveVersions(false);
```

#### Aspect Ratio

The aspect ratio can be configured per `Breakpoint` in one of the following ways:

```php
// Single aspect ratio for all breakpoints
ImageContext::make('thumbnail')
    ->aspectRatio(AspectRatio::make(1, 1));

// Different aspect ratios per breakpoint
ImageContext::make('thumbnail')
    ->aspectRatio([
        Breakpoint::Small->value => AspectRatio::make(1, 1),
        Breakpoint::Medium->value => AspectRatio::make(4, 3),
        Breakpoint::Large->value => AspectRatio::make(16, 9),
        Breakpoint::ExtraLarge->value => AspectRatio::make(16, 9),
        Breakpoint::ExtraExtraLarge->value => AspectRatio::make(2, 1),
    ]);

// Per breakpoint
ImageContext::make('thumbnail')
    ->aspectRatioForBreakpoint(Breakpoint::Small, AspectRatio::make(1, 1))

// From a Breakpoint and up
ImageContext::make('thumbnail')
    ->aspectRatioFromBreakpoint(Breakpoint::Medium, AspectRatio::make(16, 9))

// Up till a Breakpoint
ImageContext::make('thumbnail')
    ->aspectRatioUpToBreakpoint(Breakpoint::Large, AspectRatio::make(4, 3))

// Between two Breakpoints
ImageContext::make('thumbnail')
    ->aspectRatioBetweenBreakpoints(Breakpoint::Small, Breakpoint::Large, AspectRatio::make(1, 1));
```

#### Minimum width

You can define the minimum width of the image used in your design per `Breakpoint` in one of the following ways:

```php
// Single minimum width for all breakpoints
ImageContext::make('thumbnail')
    ->minWidth(150)

// Different minimum widths per breakpoint
ImageContext::make('thumbnail')
    ->minWidth([
        Breakpoint::Small->value => 100,
        Breakpoint::Medium->value => 150,
        Breakpoint::Large->value => 200,
        Breakpoint::ExtraLarge->value => 250,
        Breakpoint::ExtraExtraLarge->value => 300,
    ]);

// Per breakpoint
ImageContext::make('thumbnail')
    ->minWidthForBreakpoint(Breakpoint::Small, 100);

// From a Breakpoint and up
ImageContext::make('thumbnail')
    ->minWidthFromBreakpoint(Breakpoint::Medium, 150);

// Up till a Breakpoint
ImageContext::make('thumbnail')
    ->minWidthUpToBreakpoint(Breakpoint::Large, 200);

// Between two Breakpoints
ImageContext::make('thumbnail')
    ->minWidthBetweenBreakpoints(Breakpoint::Small, Breakpoint::Large, 150);
```

#### Maximum width

You can define the maximum width of the image used in your design per `Breakpoint` in one of the following ways:

```php
// Single maximum width for all breakpoints
ImageContext::make('thumbnail')
    ->maxWidth(250);

// Different maximum widths per breakpoint
ImageContext::make('thumbnail')
    ->maxWidth([
        Breakpoint::Small->value => 150,
        Breakpoint::Medium->value => 200,
        Breakpoint::Large->value => 250,
        Breakpoint::ExtraLarge->value => 300,
        Breakpoint::ExtraExtraLarge->value => 350,
    ]);

// Per breakpoint
ImageContext::make('thumbnail')
    ->maxWidthForBreakpoint(Breakpoint::Small, 150);

// From a Breakpoint and up
ImageContext::make('thumbnail')
    ->maxWidthFromBreakpoint(Breakpoint::Medium, 200);

// Up till a Breakpoint
ImageContext::make('thumbnail')
    ->maxWidthUpToBreakpoint(Breakpoint::Large, 250);

// Between two Breakpoints
ImageContext::make('thumbnail')
    ->maxWidthBetweenBreakpoints(Breakpoint::Small, Breakpoint::Large, 200);
```

#### Crop Position

By default, the crop position from the config file is used. You can override this per context and per `Breakpoint` in one of the following ways:

```php
// Single crop position for all breakpoints
ImageContext::make('thumbnail')
    ->cropPosition(CropPosition::Center);

// Different crop positions per breakpoint
ImageContext::make('thumbnail')
    ->cropPosition([
        Breakpoint::Small->value => CropPosition::Top,
        Breakpoint::Medium->value => CropPosition::Center,
        Breakpoint::Large->value => CropPosition::Bottom,
        Breakpoint::ExtraLarge->value => CropPosition::Center,
        Breakpoint::ExtraExtraLarge->value => CropPosition::Center,
    ]);

// Per breakpoint
ImageContext::make('thumbnail')
    ->cropPositionForBreakpoint(Breakpoint::Small, CropPosition::Top);

// From a Breakpoint and up
ImageContext::make('thumbnail')
    ->cropPositionFromBreakpoint(Breakpoint::Medium, CropPosition::Center);

// Up till a Breakpoint
ImageContext::make('thumbnail')
    ->cropPositionUpToBreakpoint(Breakpoint::Large, CropPosition::Bottom);

// Between two Breakpoints
ImageContext::make('thumbnail')
    ->cropPositionBetweenBreakpoints(Breakpoint::Small, Breakpoint::Large, CropPosition::Center);
```

#### Blur

You can apply a blur effect to images in this context per `Breakpoint` in one of the following ways:

```php
// Single blur value for all breakpoints
ImageContext::make('thumbnail')
    ->blur(10);

// Different blur values per breakpoint
ImageContext::make('thumbnail')
    ->blur([
        Breakpoint::Small->value => 5,
        Breakpoint::Medium->value => 10,
        Breakpoint::Large->value => 15,
        Breakpoint::ExtraLarge->value => 20,
        Breakpoint::ExtraExtraLarge->value => 25,
    ]);

// Per breakpoint
ImageContext::make('thumbnail')
    ->blurForBreakpoint(Breakpoint::Small, 5);

// From a Breakpoint and up
ImageContext::make('thumbnail')
    ->blurFromBreakpoint(Breakpoint::Medium, 10);

// Up till a Breakpoint
ImageContext::make('thumbnail')
    ->blurUpToBreakpoint(Breakpoint::Large, 15);

// Between two Breakpoints
ImageContext::make('thumbnail')
    ->blurBetweenBreakpoints(Breakpoint::Small, Breakpoint::Large, 10);
```

#### Greyscale

You can apply a greyscale effect to images in this context per `Breakpoint` in one of the following ways:

```php
// Single greyscale value for all breakpoints
ImageContext::make('thumbnail')
    ->greyscale(true); // or even ->grayscale(true)

// Different greyscale values per breakpoint
ImageContext::make('thumbnail')
    ->greyscale([
        Breakpoint::Small->value => false,
        Breakpoint::Medium->value => true,
        Breakpoint::Large->value => false,
        Breakpoint::ExtraLarge->value => true,
        Breakpoint::ExtraExtraLarge->value => false,
    ]);

// Per breakpoint
ImageContext::make('thumbnail')
    ->greyscaleForBreakpoint(Breakpoint::Small, false); // or even ->grayscaleForBreakpoint(Breakpoint::Small, false);

// From a Breakpoint and up
ImageContext::make('thumbnail')
    ->greyscaleFromBreakpoint(Breakpoint::Medium, true); // or even ->grayscaleFromBreakpoint(Breakpoint::Medium, true);

// Up till a Breakpoint
ImageContext::make('thumbnail')
    ->greyscaleUpToBreakpoint(Breakpoint::Large, false); // or even ->grayscaleUpToBreakpoint(Breakpoint::Large, false);

// Between two Breakpoints
ImageContext::make('thumbnail')
    ->greyscaleBetweenBreakpoints(Breakpoint::Small, Breakpoint::Large, true); // or even ->grayscaleBetweenBreakpoints(Breakpoint::Small, Breakpoint::Large, true);
```

#### Sepia

You can apply a sepia effect to images in this context per `Breakpoint` in one of the following ways:

```php
// Single sepia value for all breakpoints
ImageContext::make('thumbnail')
    ->sepia(true);

// Different sepia values per breakpoint
ImageContext::make('thumbnail')
    ->sepia([
        Breakpoint::Small->value => false,
        Breakpoint::Medium->value => true,
        Breakpoint::Large->value => false,
        Breakpoint::ExtraLarge->value => true,
        Breakpoint::ExtraExtraLarge->value => false,
    ]);

// Per breakpoint
ImageContext::make('thumbnail')
    ->sepiaForBreakpoint(Breakpoint::Small, false);

// From a Breakpoint and up
ImageContext::make('thumbnail')
    ->sepiaFromBreakpoint(Breakpoint::Medium, true);

// Up till a Breakpoint
ImageContext::make('thumbnail')
    ->sepiaUpToBreakpoint(Breakpoint::Large, false);

// Between two Breakpoints
ImageContext::make('thumbnail')
    ->sepiaBetweenBreakpoints(Breakpoint::Small, Breakpoint::Large, true);
```

### Preparing your model(s)

### Using the HasImages Trait

Add the `HasImages` trait to any Eloquent model that should support image attachments:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Outerweb\ImageLibrary\Traits\HasImages;

class Product extends Model
{
    use HasImages;

    // Your model code...
}
```

The trait provides:

-   **`images()`**: Default polymorphic relationship returning all images
-   **`attachImage()`**: Method to attach images with context validation
-   Automatic context validation and image replacement for single-image contexts

### Using Custom Relationships

For more control over image relationships, you can define custom morphic relationships alongside the `HasImages` trait. This allows you to create type-specific relationships for different image contexts.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Traits\HasImages;

class Article extends Model
{
    use HasImages;

    /**
     * Single featured image relationship
     */
    public function featuredImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'model')
                    ->where('context', 'featured');
    }

    /**
     * Multiple gallery images relationship
     */
    public function galleryImages(): MorphMany
    {
        return $this->morphMany(Image::class, 'model')
                    ->where('context', 'gallery');
    }

    /**
     * Images for a specific layout block (useful for page builders)
     */
    public function getLayoutBlockImages(int $blockId): MorphMany
    {
        return $this->images()
            ->whereJsonContains('custom_properties->layout_block_id', $blockId);
    }
}
```

### Custom Breakpoints

If the default breakpoints don't match your design system, you can create a custom breakpoint enum. This is useful when you need different screen size thresholds or additional breakpoints.

#### Creating a Custom Breakpoint Enum

First, create a custom enum that implements the `ConfiguresBreakpoints` contract:

```php
<?php

namespace App\Enums;

use Illuminate\Support\Str;
use Outerweb\ImageLibrary\Contracts\ConfiguresBreakpoints;

enum CustomBreakpoint: string implements ConfiguresBreakpoints
{
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Desktop = 'desktop';
    case UltraWide = 'ultrawide';

    public static function sortedCases(): array
    {
        return collect(self::cases())
            ->sort(fn ($a, $b) => $a->getMinWidth() <=> $b->getMinWidth())
            ->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Mobile => 'Mobile',
            self::Tablet => 'Tablet',
            self::Desktop => 'Desktop',
            self::UltraWide => 'Ultra Wide',
        };
    }

    public function getMinWidth(): int
    {
        return match ($this) {
            self::Mobile => 320,
            self::Tablet => 768,
            self::Desktop => 1200,
            self::UltraWide => 1920,
        };
    }

    public function getMaxWidth(): ?int
    {
        $index = array_search($this, self::sortedCases(), true);
        $next = self::sortedCases()[$index + 1] ?? null;

        return $next ? $next->getMinWidth() - 1 : null;
    }

    public function getSlug(): string
    {
        return Str::of($this->value)
            ->lower()
            ->slug()
            ->toString();
    }
}
```

#### Configuring the Custom Breakpoint Enum

Update your `config/image-library.php` file to use your custom enum:

```php
'enums' => [
    'breakpoint' => App\Enums\CustomBreakpoint::class,
],
```

## Usage

### Uploading an image

Upload images from `UploadedFile` instances (typically from form submissions) to create `SourceImage` records:

#### Basic Upload

```php
use Outerweb\ImageLibrary\Facades\ImageLibrary;

// Basic upload using default settings
$sourceImage = ImageLibrary::upload($request->file('image'));

// The SourceImage is now stored and optimized, ready to be attached to models
```

#### Upload with Custom Attributes

```php
// Upload to specific disk
$sourceImage = ImageLibrary::upload($request->file('image'), [
    'disk' => 's3',
]);

// Upload with custom properties and metadata
$sourceImage = ImageLibrary::upload($request->file('image'), [
    'disk' => 's3',
    'custom_properties' => [
        'photographer' => 'John Doe',
        'license' => 'Creative Commons',
        'shoot_date' => '2024-01-15',
        'camera_model' => 'Canon EOS R5'
    ],
]);
```

#### What Happens During Upload

1. **Automatic optimization**: Images are processed using Spatie Image with your configured driver (GD/Imagick)
2. **Metadata extraction**: Width, height, file size, and MIME type are automatically detected and stored
3. **UUID generation**: A unique identifier is created for organized file storage
4. **File organization**: Images are stored in a structured directory: `{base_path}/{uuid}/original.{extension}`
5. **Database record**: A `SourceImage` model is created with all metadata

### Attaching an image to your model

After uploading a `SourceImage`, attach it to your models using the context system:

#### Basic Attachment

```php
// Upload the source image
$sourceImage = ImageLibrary::upload($request->file('image'));

// Get your model
$product = Product::find(1);

// Attach with a context
$image = $product->attachImage($sourceImage, [
    'context' => 'thumbnail'
]);

// The image is now attached and will be processed according to the context configuration
```

#### Advanced Attachment Examples

```php
// Attach with multilingual alt text
$image = $product->attachImage($sourceImage, [
    'context' => 'featured_image',
    'alt_text' => [
        'en' => 'Taylor Otwell driving his lamborghini',
        'nl' => 'Taylor Otwell rijdt in zijn lamborghini',
    ]
]);

// Attach with custom properties and metadata
$image = $product->attachImage($sourceImage, [
    'context' => 'gallery',
    'custom_properties' => [
        'photographer' => 'Jane Smith',
        'copyright' => '© 2024 Company Name',
        'location' => 'Swiss Alps',
        'camera_settings' => [
            'aperture' => 'f/2.8',
            'shutter_speed' => '1/500s',
            'iso' => 200
        ]
    ],
    'alt_text' => [
        'en' => 'Mountain landscape photography'
    ]
]);
```

#### Attaching to a custom relationship

When using custom relationships, you can still use the `attachImage` method. You can specify the relationship to use:

```php
$image = $product->attachImage($sourceImage, [
    'context' => 'featured'
], 'featuredImage');
```

#### Context-Specific Behavior

The `attachImage` method will replace the existing image if the context is not configured to allow multiple images. This ensures that single-image contexts always have only one associated image.

### Using your model image(s)

You can access your model's images through the `images` relationship or any custom relationships you've defined.

```php
// Get all images
$images = $product->images;

// Query images by context
$thumbnails = $product->images()
    ->where('context', 'thumbnail')
    ->get();

// Query images with specific custom properties
$landscapeImages = $product->images()
    ->where('context', 'gallery')
    ->whereJsonContains('custom_properties->layout_builder_block_id', $blockId)
    ->get();
```

### Rendering images

You can render images in your views using the provided view component:

```blade
<x-image-library::image
    :image="$image"
    class="rounded-lg w-1/2"
/>
```

This will render a `picture` element with the following:

-   a `source` element per `Breakpoint` with responsive image urls
-   a `source` element per `Breakpoint` for the WebP versions of the responsive image urls
-   an `img` element with the default image url, alt text, and any additional attributes you provide

Make sure you added the script component to the `<head>` of your layout:

```blade
<x-image-library::scripts />
```

This script will set all `sizes` attributes of the picture elements automatically when:

-   The page is loaded
-   The viewport is resized
-   The picture element is added to the viewport
-   The picture element width changes

## Upgrading

### From v2.x to v3.0

This is a major version with breaking changes. See the [Upgrade guide](./docs/upgrade-to-v3.md) for detailed instructions.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
