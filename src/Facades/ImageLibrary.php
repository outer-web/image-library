<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Facades;

use BackedEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Outerweb\ImageLibrary\Contracts\ConfiguresBreakpoints;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\SourceImage;
use Spatie\Image\Enums\CropPosition;
use Spatie\Image\Image as SpatieImage;

/**
 * @method static class-string<BackedEnum&ConfiguresBreakpoints> getBreakpointEnum()
 * @method static class-string<Image> getImageModel()
 * @method static class-string<SourceImage> getSourceImageModel()
 * @method static SpatieImage getSpatieImage()
 * @method static void registerImageContexts(array<ImageContext> $imageContexts)
 * @method static void registerImageContext(ImageContext $imageContext)
 * @method static void removeImageContext(ImageContext $imageContext)
 * @method static array<ImageContext> getImageContexts()
 * @method static ?ImageContext getImageContextByKey(?string $key)
 * @method static SourceImage upload(UploadedFile $file, array $attributes = [])
 * @method static bool shouldUseTemporaryUrlsForDisk(string $disk)
 * @method static int getTemporaryUrlExpirationMinutesForDisk(string $disk)
 * @method static string getDefaultDisk()
 * @method static array<string> getSupportedLocales()
 * @method static CropPosition getDefaultCropPosition()
 * @method static bool shouldGenerateWebp()
 * @method static bool shouldGenerateResponsiveVersions()
 * @method static string getDefaultQueueConnection()
 * @method static string getDefaultQueue()
 * @method static string getBasePath()
 * @method static float getResponsiveImageSizeStepMultiplier()
 * @method static int getResponsiveImageWidthDifferenceThreshold()
 * @method static int getResponsiveImageMinWidth()
 *
 * @see \Outerweb\ImageLibrary\ImageLibrary
 */
class ImageLibrary extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Outerweb\ImageLibrary\ImageLibrary::class;
    }
}
