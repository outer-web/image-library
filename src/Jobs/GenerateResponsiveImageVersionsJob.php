<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Outerweb\ImageLibrary\Contracts\ConfiguresBreakpoints;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\Image;
use Spatie\Image\Enums\Fit;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class GenerateResponsiveImageVersionsJob implements ShouldQueue
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

        File::put($temporaryPath, $image->getForBreakpoint($this->breakpoint));

        $widths = $this->calculateWidths(
            $temporaryPath,
            $image,
        );

        foreach ($widths as $width) {
            $file = ImageLibrary::getSpatieImage()
                ->loadFile($temporaryPath)
                ->fit(Fit::Max, $width);

            // Create directory
            Storage::disk($image->disk)->makeDirectory($image->getRelativeBasePath());

            $file->save(Str::of($image->getAbsolutePathForBreakpoint($this->breakpoint))
                ->replaceLast('.'.$image->sourceImage->extension, '_w'.$width.'.'.$image->sourceImage->extension)
                ->toString());

            if ($image->context->getGenerateWebP()) {
                $file->save(
                    Str::of($image->getAbsolutePathForBreakpoint($this->breakpoint, 'webp'))
                        ->replaceLast('.webp', '_w'.$width.'.webp')
                        ->toString()
                );
            }
        }
    }

    private function calculateWidths(string $path, Image $image): array
    {
        $file = ImageLibrary::getSpatieImage()->loadFile($path);

        $fileWidth = $file->getWidth();
        $fileHeight = $file->getHeight();
        $fileSize = File::size($path);

        $contextMaxWidth = $image->context->getMaxWidthForBreakpoint($this->breakpoint);
        $contextMinWidth = $image->context->getMinWidthForBreakpoint($this->breakpoint);

        $breakpointMinWidth = $this->breakpoint->getMinWidth();

        $minWidth = min(is_null($contextMaxWidth) ? $breakpointMinWidth : ($contextMinWidth ?? 0), ImageLibrary::getResponsiveImageMinWidth());

        $ratio = $fileHeight / $fileWidth;
        $area = $fileWidth * $fileHeight;
        $pixelPrice = $fileSize / $area;

        $sizeStepMultiplier = ImageLibrary::getResponsiveImageSizeStepMultiplier();
        $widthDiffThreshold = ImageLibrary::getResponsiveImageWidthDifferenceThreshold();

        $widths = [];
        $predictedFileSize = $fileSize;
        $prevWidth = $fileWidth;

        while (true) {
            $predictedFileSize *= $sizeStepMultiplier;

            $newWidth = (int) floor(sqrt(($predictedFileSize / $pixelPrice) / $ratio));

            if ($newWidth < $minWidth || $newWidth >= $prevWidth) {
                break;
            }

            if (($prevWidth - $newWidth) < $widthDiffThreshold) {
                $prevWidth = $newWidth;

                continue;
            }

            $widths[] = $newWidth;

            $prevWidth = $newWidth;
        }

        return array_values(array_unique($widths));
    }
}
