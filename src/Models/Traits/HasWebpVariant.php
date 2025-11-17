<?php

namespace Outerweb\ImageLibrary\Models\Traits;

use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image as SpatieImage;

trait HasWebpVariant
{
    public function generateWebpVariant(bool $force = false): void
    {
        if ($this->hasWebpVariant() && !$force) {
            return;
        }

        SpatieImage::useImageDriver(config('image-library.image_driver'))
            ->loadFile($this->getPath())
            ->background('rgba(255, 255, 255, 0)')
            ->optimize()
            ->save(Storage::disk($this->disk)->path($this->getWebpShortPath()));
    }

    public function hasWebpVariant(): bool
    {
        return Storage::disk($this->disk)->exists($this->getWebpShortPath());
    }

    public function getWebpShortPath(bool $includeFileExtension = true): string
    {
        $path = $this->getShortPath(false);

        if ($includeFileExtension) {
            $path .= '.webp';
        }

        return $path;
    }

    public function getWebpPath(): string
    {
        return Storage::disk($this->disk)->path($this->getWebpShortPath());
    }

    public function getWebpUrl(): string
    {
        return Storage::disk($this->disk)->url($this->getWebpShortPath());
    }
}
