<?php

namespace Outerweb\ImageLibrary\Models\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Image\Image as SpatieImage;

trait HasResponsiveVariants
{
    public function generateResponsiveVariants(bool $force = false): void
    {
        if ($this->hasResponsiveVariants() && !$force) {
            return;
        }

        if ($force) {
            $this->deleteResponsiveVariants();
        }

        $baseImage = SpatieImage::useImageDriver(config('image-library.image_driver'))
            ->loadFile($this->getPath());

        $this->generateVariantsRecursive($baseImage);
    }

    public function generateVariantsRecursive(SpatieImage $image): void
    {
        $width = $image->getWidth();
        $height = $image->getHeight();

        $factor = $this->getResponsiveFactor();

        $newWidth = round($width * $factor);
        $newHeight = round($height * $factor);

        if ($newWidth < $this->getResponsiveMinWidth() || $newHeight < $this->getResponsiveMinHeight()) {
            // Minimum size reached, stop recursion
            return;
        }

        $image->width($newWidth)
            ->height($newHeight)
            ->background('rgba(255, 255, 255, 0)');

        $pathInfo = pathinfo($this->getPath());
        $variantFileName = $pathInfo['filename'] . "_{$newWidth}x{$newHeight}.{$pathInfo['extension']}";
        $variantPath = $this->getBasePath() . '/' . $variantFileName;

        $image->save(Storage::disk($this->disk)->path($variantPath));

        if (config('image-library.support.webp')) {
            $pathInfo = pathinfo($this->getPath());
            $variantFileName = $pathInfo['filename'] . "_{$newWidth}x{$newHeight}.webp";
            $variantPath = $this->getBasePath() . '/' . $variantFileName;
            $image->save(Storage::disk($this->disk)->path($variantPath));
        }

        // Recursively generate smaller variants
        $this->generateVariantsRecursive($image);
    }

    public function getResponsiveVariants(bool $asWebp = false): Collection
    {
        $variants = collect();

        foreach (Storage::disk($this->disk)->allFiles($this->getBasePath()) as $file) {
            if (preg_match("/\/{$this->file_name}_/", $file)) {
                if (!Str::endsWith($file, '.' . ($asWebp ? 'webp' : $this->file_extension))) {
                    continue;
                }

                $widthAndHeight = Str::beforeLast(Str::afterLast($file, $this->file_name . '_'), '.');
                $width = (int) explode('x', $widthAndHeight)[0];
                $height = (int) explode('x', $widthAndHeight)[1];
                $variants->push((object) [
                    'path' => $file,
                    'url' => Storage::disk($this->disk)->url($file),
                    'width' => $width,
                    'height' => $height,
                ]);
            }
        }

        return $variants->sortBy('width');
    }

    public function deleteResponsiveVariants(): void
    {
        $this->getResponsiveVariants()->each(function ($variant) {
            Storage::disk($this->disk)->delete($variant->path);
        });

        if (config('image-library.support.webp')) {
            $this->getResponsiveVariants(true)->each(function ($variant) {
                Storage::disk($this->disk)->delete($variant->path);
            });
        }
    }

    public function hasResponsiveVariants(): bool
    {
        return $this->getResponsiveVariants()->isNotEmpty();
    }

    public function getResponsiveFactor(): float
    {
        $factor = config('image-library.responsive_variants.factor');

        if ($factor < 0.1 || $factor > 1) {
            throw new \Exception('Responsive variant factor must be between 0.1 and 1');
        }

        return $factor;
    }

    public function getResponsiveMinWidth(): int
    {
        return config('image-library.responsive_variants.min_width');
    }

    public function getResponsiveMinHeight(): int
    {
        return config('image-library.responsive_variants.min_height');
    }
}
