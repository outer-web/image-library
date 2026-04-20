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
use Spatie\Image\Enums\FlipDirection;
use Spatie\Image\Enums\Orientation;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class GenerateImageVersionJob implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public function __construct(
        public mixed $imageId,
        public ?ConfiguresBreakpoints $breakpoint = null,
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

        $slug = $this->breakpoint?->getSlug() ?? 'default';
        $temporaryPath = new TemporaryDirectory()->create()->path($slug.'-'.$image->uuid.'.'.$image->sourceImage->extension);

        File::put($temporaryPath, $image->sourceImage->get());

        $file = ImageLibrary::getSpatieImage()
            ->loadFile($temporaryPath)
            ->optimize();

        $cropDataKey = $this->breakpoint->value ?? 'default';
        $cropData = $image->crop_data[$cropDataKey] ?? null;

        if (! is_null($cropData)) {
            if (is_null($cropData->x) || is_null($cropData->y)) {
                $cropPosition = $image->context
                    ? $image->context->getCropPosition($this->breakpoint)
                    : ImageLibrary::getDefaultCropPosition();
                $file->crop($cropData->width, $cropData->height, $cropPosition);
            } else {
                $file->manualCrop(
                    $cropData->width,
                    $cropData->height,
                    $cropData->x,
                    $cropData->y,
                );
            }

            if (is_int($cropData->scaleX) && $cropData->scaleX === -1) {
                $file->flip(FlipDirection::Horizontal);
            }

            if (is_int($cropData->scaleY) && $cropData->scaleY === -1) {
                $file->flip(FlipDirection::Vertical);
            }

            if (is_int($cropData->rotate) && $cropData->rotate !== 0) {
                $orientation = Orientation::tryFrom((int) ($cropData->rotate));

                if ($orientation) {
                    $file->orientation($orientation);
                }
            }
        } elseif ($image->context) {
            $aspectRatio = $image->context->getAspectRatio($this->breakpoint);

            if ($aspectRatio) {
                $fileWidth = $file->getWidth();
                $fileHeight = $file->getHeight();
                $targetRatio = $aspectRatio->horizontal / $aspectRatio->vertical;
                $fileRatio = $fileWidth / $fileHeight;

                if ($fileRatio > $targetRatio) {
                    $cropWidth = (int) floor($fileHeight * $targetRatio);
                    $cropHeight = $fileHeight;
                } else {
                    $cropWidth = $fileWidth;
                    $cropHeight = (int) floor($fileWidth / $targetRatio);
                }

                $file->crop($cropWidth, $cropHeight, $image->context->getCropPosition($this->breakpoint));
            }
        }

        if ($image->context) {
            $breakpointMaxWidth = $image->context->getMaxWidth($this->breakpoint);

            if (! is_null($breakpointMaxWidth)) {
                $file->fit(Fit::Max, $breakpointMaxWidth);
            }

            $blur = $image->context->getBlur($this->breakpoint);
            if (is_int($blur)) {
                $file->blur($blur);
            }

            if ($image->context->getGreyscale($this->breakpoint)) {
                $file->greyscale();
            }

            if ($image->context->getSepia($this->breakpoint)) {
                $file->sepia();
            }
        }

        // Create directory
        Storage::disk($image->disk)->makeDirectory($image->getRelativeBasePath());

        $file->save($image->getAbsolutePathForBreakpoint($this->breakpoint));

        $shouldGenerateWebP = $image->context
            ? $image->context->getGenerateWebP()
            : ImageLibrary::shouldGenerateWebp();

        if ($shouldGenerateWebP) {
            $file->save($image->getAbsolutePathForBreakpoint($this->breakpoint, 'webp'));
        }
    }
}
