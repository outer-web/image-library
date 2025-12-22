<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\SourceImage;

/**
 * @extends Factory<SourceImage>
 */
class SourceImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'disk' => ImageLibrary::getDefaultDisk(),
            'name' => fake()->word(),
            'extension' => fake()->randomElement(['jpg', 'png', 'webp']),
            'mime_type' => fake()->mimeType(),
            'width' => fake()->numberBetween(100, 4000),
            'height' => fake()->numberBetween(100, 4000),
            'size' => fake()->numberBetween(1000, 1000000),
            'alt_text' => fake()->boolean() ? collect(ImageLibrary::getSupportedLocales())
                ->mapWithKeys(fn (string $locale) => [$locale => fake()->sentence()])
                ->all() : null,
        ];
    }
}
