<?php

declare(strict_types=1);

use Outerweb\ImageLibrary\Enums\Breakpoint;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\SourceImage;
use Spatie\Image\Enums\CropPosition;
use Spatie\Image\Enums\ImageDriver;

// config for Outerweb/ImageLibrary
return [
    'defaults' => [
        'crop_position' => CropPosition::Center,
        'disk' => 'public',
        'temporary_url' => [
            'default' => [
                'enabled' => false,
                'expiration_minutes' => 5,
            ],
            's3' => [
                'enabled' => true,
            ],
        ],
    ],
    'enums' => [
        'breakpoint' => Breakpoint::class,
    ],
    'generate' => [
        'webp' => true,
        'responsive_versions' => true,
    ],
    'models' => [
        'image' => Image::class,
        'source_image' => SourceImage::class,
    ],
    'paths' => [
        'base' => 'image-library',
    ],
    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'sync'),
        'queue' => 'default',
    ],
    'spatie_image' => [
        'driver' => ImageDriver::Imagick,
    ],
    'responsive_images' => [
        'width_difference_threshold' => 100,
        'size_step_multiplier' => 0.7,
        'min_width' => 100,
    ],
];
