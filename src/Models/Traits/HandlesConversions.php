<?php

namespace Outerweb\ImageLibrary\Models\Traits;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Jobs\GenerateConversion;
use Outerweb\ImageLibrary\Jobs\GenerateResponsiveVariants;
use Outerweb\ImageLibrary\Jobs\GenerateWebpVariant;
use Outerweb\ImageLibrary\Models\ImageConversion;
use Spatie\Image\Enums\CropPosition;
use Spatie\Image\Enums\FlipDirection;
use Spatie\Image\Enums\Orientation;
use Spatie\Image\Image as SpatieImage;

trait HandlesConversions
{
    public static function bootHandlesConversions(): void
    {
        self::deleting(function (ImageConversion $conversion) {
            $conversion->deleteFiles();
        });

        self::created(function (ImageConversion $conversion) {
            $conversionDefinition = ImageLibrary::getConversionDefinition($conversion->conversion_name);

            if ($conversionDefinition->create_sync) {
                GenerateConversion::dispatchSync($conversion);
            } else {
                GenerateConversion::dispatch($conversion);
            }
        });

        self::updated(function (ImageConversion $conversion) {
            if ($conversion->wasChanged(['x', 'y', 'width', 'height', 'rotate', 'scale_x', 'scale_y', 'conversion_name'])) {
                $conversionDefinition = ImageLibrary::getConversionDefinition($conversion->conversion_name);

                if ($conversionDefinition->create_sync) {
                    GenerateConversion::dispatchSync($conversion);
                } else {
                    GenerateConversion::dispatch($conversion);
                }
            }
        });
    }
    public function generate(bool $force = false): void
    {
        if ($this->exists() && !$force) {
            return;
        }

        $conversionFile = SpatieImage::useImageDriver(config('image-library.image_driver'))
            ->loadFile($this->image->getPath());

        if ($this->x || $this->y) {
            $conversionFile
                ->manualCrop($this->width, $this->height, $this->x, $this->y);
        } else {
            $conversionFile
                ->crop($this->width, $this->height, CropPosition::Center);
        }

        $conversionFile
            ->orientation(match ($this->rotate) {
                90 => Orientation::Rotate90,
                180 => Orientation::Rotate180,
                270 => Orientation::Rotate270,
                default => Orientation::Rotate0,
            });

        $flipDirection = null;
        if ($this->scale_x === -1 && $this->scale_y === 1) {
            $flipDirection = FlipDirection::Horizontal;
        } elseif ($this->scale_x === 1 && $this->scale_y === -1) {
            $flipDirection = FlipDirection::Vertical;
        } elseif ($this->scale_x === -1 && $this->scale_y === -1) {
            $flipDirection = FlipDirection::Both;
        }

        if ($flipDirection) {
            $conversionFile->flip($flipDirection);
        }

        $conversionData = ImageLibrary::getConversionDefinition($this->conversion_name);

        $conversionData->validate(true);

        $effects = $conversionData->effects;

        if (!is_null($effects->blur)) {
            $conversionFile->blur($effects->blur);
        }

        if ($effects->pixelate) {
            $conversionFile->pixelate($effects->pixelate);
        }

        if ($effects->greyscale) {
            $conversionFile->greyscale();
        }

        if ($effects->sepia) {
            $conversionFile->sepia();
        }

        if ($effects->sharpen) {
            $conversionFile->sharpen($effects->sharpen);
        }

        $conversionFile
            ->optimize()
            ->save($this->getPath());

        $this->update([
            'size' => Storage::disk($this->disk)->size($this->getShortPath()),
        ]);

        if (config('image-library.support.webp')) {
            GenerateWebpVariant::dispatch($this, $force);
        }

        if (config('image-library.support.responsive_variants')) {
            GenerateResponsiveVariants::dispatch($this, $force);
        }
    }

    public function deleteFiles(): void
    {
        foreach (Storage::disk($this->image->disk)->allFiles($this->getBasePath()) as $file) {
            if (preg_match('/\/' . $this->file_name . '(_|\.)?/', $file)) {
                Storage::disk($this->image->disk)->delete($file);
            }
        }
    }
}
