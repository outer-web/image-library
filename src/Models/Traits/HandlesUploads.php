<?php

namespace Outerweb\ImageLibrary\Models\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Outerweb\ImageLibrary\Jobs\GenerateResponsiveVariants;
use Outerweb\ImageLibrary\Jobs\GenerateWebpVariant;
use Outerweb\ImageLibrary\Models\Image;
use Spatie\Image\Image as SpatieImage;

trait HandlesUploads
{
    public static function bootHandlesUploads(): void
    {
        self::deleting(function (Image $image) {
            $image->deleteFiles();
        });
    }

    public static function upload(UploadedFile $file, ?string $disk = null, array $attributes = []): self
    {
        self::validateFile($file);

        $disk = $disk ?? config('image-library.default_disk');

        try {
            DB::beginTransaction();

            /** @var Image $image */
            $image = self::create([
                'disk' => $disk,
                'mime_type' => $file->getMimeType(),
                'file_extension' => $file->getClientOriginalExtension(),
                'width' => getimagesize($file->getPathname())[0],
                'height' => getimagesize($file->getPathname())[1],
                'size' => $file->getSize(),
                'title' => $attributes['title'] ?? null,
                'alt' => $attributes['alt'] ?? null,
            ]);

            $image->createBasePath();

            SpatieImage::useImageDriver(config('image-library.image_driver'))
                ->loadFile($file->getPathname())
                ->optimize()
                ->save($image->getPath());

            if (config('image-library.support.webp')) {
                GenerateWebpVariant::dispatch($image);
            }

            if (config('image-library.support.responsive_variants')) {
                GenerateResponsiveVariants::dispatch($image);
            }

            $image->createOrUpdateConversions();

            DB::commit();
        } catch (\Exception $e) {
            if (isset($image)) {
                $image->deleteFiles();
            }

            DB::rollBack();

            throw $e;
        }

        return $image;
    }

    public static function validateFile(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), self::getSupportedMimeTypes())) {
            throw new \Exception("The file type {$file->getMimeType()} is not supported");
        }

        if (!is_null(self::getMaxFilesize()) && $file->getSize() > self::getMaxFileSize()) {
            throw new \Exception("The file size {$file->getSize()} is too large. The maximum file size is " . self::getMaxFileSize() . " bytes");
        }
    }

    public static function getSupportedMimeTypes(): array
    {
        return config('image-library.support.mime_types', []);
    }

    public static function getMaxFileSize(): int
    {
        $maxFileSize = config('image-library.max_file_size');

        if (is_numeric($maxFileSize)) {
            return $maxFileSize;
        }

        $maxFileSize = strtoupper(trim($maxFileSize));
        $stringPart = (string) preg_replace('/[^a-zA-Z]/', '', $maxFileSize);
        $valuePart = (int) preg_replace('/[^0-9]/', '', $maxFileSize) ?: 0;

        $units = [
            'B' => 1,
            'KB' => 1024,
            'MB' => 1024 * 1024,
            'GB' => 1024 * 1024 * 1024,
            'TB' => 1024 * 1024 * 1024 * 1024,
        ];

        foreach ($units as $unit => $factor) {
            if ($stringPart === $unit) {
                return $valuePart * $factor;
            }
        }

        throw new InvalidArgumentException('Invalid image-library.max_file_size value');
    }

    public function deleteFiles(): void
    {
        Storage::disk($this->disk)->deleteDirectory($this->getBasePath());
    }
}
