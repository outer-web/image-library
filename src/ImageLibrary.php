<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary;

use BackedEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Outerweb\ImageLibrary\Contracts\ConfiguresBreakpoints;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\SourceImage;
use Spatie\Image\Enums\CropPosition;
use Spatie\Image\Image as SpatieImage;

class ImageLibrary
{
    protected array $imageContexts = [];

    /**
     * @return class-string<BackedEnum&ConfiguresBreakpoints>
     */
    public function getBreakpointEnum(): string
    {
        return Config::get('image-library.enums.breakpoint');
    }

    /**
     * @return class-string<Image>
     */
    public function getImageModel(): string
    {
        return Config::get('image-library.models.image');
    }

    /**
     * @return class-string<SourceImage>
     */
    public function getSourceImageModel(): string
    {
        return Config::get('image-library.models.source_image');
    }

    public function getSpatieImage(): SpatieImage
    {
        return SpatieImage::useImageDriver(Config::get('image-library.spatie_image.driver'));
    }

    /**
     * @param  array<ImageContext>  $imageContexts
     */
    public function registerImageContexts(array $imageContexts): void
    {
        foreach ($imageContexts as $imageContext) {
            if (! $imageContext instanceof ImageContext) {
                throw new InvalidArgumentException('Expected instance of ImageContext, but got '.gettype($imageContext).' instead.');
            }

            $this->imageContexts[$imageContext->getKey()] = $imageContext;
        }
    }

    public function registerImageContext(ImageContext $imageContext): void
    {
        $this->imageContexts[$imageContext->getKey()] = $imageContext;
    }

    public function removeImageContext(ImageContext $imageContext): void
    {
        unset($this->imageContexts[$imageContext->getKey()]);
    }

    /**
     * @return array<ImageContext>
     */
    public function getImageContexts(): array
    {
        return array_values($this->imageContexts);
    }

    public function getImageContextByKey(?string $key): ?ImageContext
    {
        if (blank($key)) {
            return null;
        }

        return $this->imageContexts[$key] ?? null;
    }

    public function upload(UploadedFile $file, array $attributes = []): SourceImage
    {
        return $this->getSourceImageModel()::upload($file, $attributes);
    }

    public function shouldGenerateWebp(): bool
    {
        return Config::get('image-library.generate.webp', true);
    }

    public function shouldGenerateResponsiveVersions(): bool
    {
        return Config::get('image-library.generate.responsive_versions', true);
    }

    public function shouldUseTemporaryUrlsForDisk(string $disk): bool
    {
        if (! Config::has("image-library.defaults.temporary_url.$disk")) {
            return Config::get('image-library.defaults.temporary_url.default.enabled', false);
        }

        return Config::get("image-library.defaults.temporary_url.$disk.enabled", false);
    }

    public function getTemporaryUrlExpirationMinutesForDisk(string $disk): int
    {
        if (! Config::has("image-library.defaults.temporary_url.$disk")) {
            return Config::get('image-library.defaults.temporary_url.default.expiration_minutes', 5);
        }

        return Config::get("image-library.defaults.temporary_url.$disk.expiration_minutes", 5);
    }

    public function getDefaultDisk(): string
    {
        return Config::string('image-library.defaults.disk');
    }

    /** @return array<string> */
    public function getSupportedLocales(): array
    {
        return Config::array('app.supported_locales', [Config::string('app.locale')]);
    }

    public function getDefaultCropPosition(): CropPosition
    {
        $position = Config::get('image-library.defaults.crop_position', CropPosition::Center);

        return $position instanceof CropPosition ? $position : CropPosition::from($position);
    }

    public function getDefaultQueueConnection(): string
    {
        return Config::string('image-library.queue.connection');
    }

    public function getDefaultQueue(): string
    {
        return Config::string('image-library.queue.queue');
    }

    public function getBasePath(): string
    {
        return Config::string('image-library.paths.base');
    }

    public function getResponsiveImageSizeStepMultiplier(): float
    {
        return Config::float('image-library.responsive_images.size_step_multiplier', 0.7);
    }

    public function getResponsiveImageWidthDifferenceThreshold(): int
    {
        return Config::integer('image-library.responsive_images.width_difference_threshold', 50);
    }

    public function getResponsiveImageMinWidth(): int
    {
        return Config::integer('image-library.responsive_images.min_width', 100);
    }
}
