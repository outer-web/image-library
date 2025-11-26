<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Outerweb\ImageLibrary\Contracts\ConfiguresBreakpoints;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\Image;
use Spatie\Image\Enums\Fit;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class GenerateImageVersionJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(
        public mixed $imageId,
        public ConfiguresBreakpoints $breakpoint,
    ) {
        $this->imageId = $imageId instanceof Image ? $imageId->getKey() : $imageId;

        $this->onConnection(ImageLibrary::getDefaultQueueConnection());
        $this->onQueue(ImageLibrary::getDefaultQueue());
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $image = ImageLibrary::getImageModel()::query()
            ->findOrFail($this->imageId);

        $temporaryPath = new TemporaryDirectory()->create()->path($this->breakpoint->getSlug().'-'.$image->uuid.'.'.$image->sourceImage->extension);

        File::put($temporaryPath, $image->sourceImage->get());

        $file = ImageLibrary::getSpatieImage()
            ->loadFile($temporaryPath)
            ->optimize();

        $cropData = $image->crop_data[$this->breakpoint->value] ?? null;

        if (! is_null($cropData)) {
            if (is_null($cropData->x) || is_null($cropData->y)) {
                $file->crop($cropData->width, $cropData->height, $image->context->getCropPositionForBreakpoint($this->breakpoint));
            } else {
                $file->manualCrop(
                    $cropData->width,
                    $cropData->height,
                    $cropData->x,
                    $cropData->y,
                );
            }
        } else {
            $fileWidth = $file->getWidth();
            $maxWidth = min($image->context->getMaxWidthForBreakpoint($this->breakpoint) ?? $fileWidth, $fileWidth);
            $maxHeight = $file->getHeight();
            $aspectRatio = $image->context->getAspectRatioForBreakpoint($this->breakpoint);

            $possibleWidth = $maxHeight * $aspectRatio->horizontal / $aspectRatio->vertical;
            $possibleHeight = $maxWidth * $aspectRatio->vertical / $aspectRatio->horizontal;

            // @codeCoverageIgnoreStart
            if ($possibleWidth <= $maxWidth) {
                $width = (int) round($possibleWidth);
                $height = $maxHeight;
            } else {
                $width = $maxWidth;
                $height = (int) round($possibleHeight);
            }
            // @codeCoverageIgnoreEnd

            $file->crop($width, $height, $image->context->getCropPositionForBreakpoint($this->breakpoint));
        }

        $breakpointMaxWidth = $image->context->getMaxWidthForBreakpoint($this->breakpoint);

        if (! is_null($breakpointMaxWidth)) {
            $file->fit(Fit::Max, $breakpointMaxWidth);
        }

        $blur = $image->context->getBlurForBreakpoint($this->breakpoint);
        if (is_int($blur)) {
            $file->blur($blur);
        }

        $greyscale = $image->context->getGreyscaleForBreakpoint($this->breakpoint);
        if ($greyscale === true) {
            $file->greyscale();
        }

        $sepia = $image->context->getSepiaForBreakpoint($this->breakpoint);
        if ($sepia === true) {
            $file->sepia();
        }

        // Create directory
        Storage::disk($image->disk)->makeDirectory($image->getRelativeBasePath());

        $file->save($image->getAbsolutePathForBreakpoint($this->breakpoint));

        if ($image->context->getGenerateWebP()) {
            $file->save($image->getAbsolutePathForBreakpoint($this->breakpoint, 'webp'));
        }
    }
}
