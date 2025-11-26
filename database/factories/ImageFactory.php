<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Outerweb\ImageLibrary\Entities\CropData;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\Image;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_image_id' => ImageLibrary::getSourceImageModel()::factory(),
            'disk' => function (array $attributes) {
                return ImageLibrary::getSourceImageModel()::find($attributes['source_image_id'])->disk ?? ImageLibrary::getDefaultDisk();
            },
            'context' => fake()->randomElement(ImageLibrary::getImageContexts()),
            'crop_data' => function (array $attributes): array {
                $sourceImage = ImageLibrary::getSourceImageModel()::find($attributes['source_image_id']);

                return collect(ImageLibrary::getBreakpointEnum()::sortedCases())->mapWithKeys(function ($breakpoint) use ($sourceImage): array {
                    $maxWidth = $sourceImage->width ?? 4000;
                    $maxHeight = $sourceImage->height ?? 4000;

                    $width = fake()->numberBetween(10, $maxWidth);
                    $height = fake()->numberBetween(10, $maxHeight);

                    return [$breakpoint->value => new CropData(
                        x: fake()->numberBetween(0, max(0, $maxWidth - $width)),
                        y: fake()->numberBetween(0, max(0, $maxHeight - $height)),
                        width: $width,
                        height: $height,
                    )];
                })
                    ->all();
            },
            'alt_text' => fake()->boolean() ? collect(ImageLibrary::getSupportedLocales())
                ->mapWithKeys(fn (string $locale) => [$locale => fake()->sentence()])
                ->all() : null,
        ];
    }

    public function forModel(Model $model): self
    {
        return $this->state(function () use ($model) {
            return [
                'model_type' => $model::class,
                'model_id' => $model->getKey(),
            ];
        });
    }

    public function forContext(ImageContext $context): self
    {
        return $this->state(function () use ($context) {
            return [
                'context' => $context,
            ];
        });
    }
}
