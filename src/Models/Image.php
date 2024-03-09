<?php

namespace Outerweb\ImageLibrary\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Outerweb\ImageLibrary\Models\Traits\GeneratesUuids;
use Outerweb\ImageLibrary\Models\Traits\HandlesUploads;
use Outerweb\ImageLibrary\Models\Traits\HasConversions;
use Outerweb\ImageLibrary\Models\Traits\HasResponsiveVariants;
use Outerweb\ImageLibrary\Models\Traits\HasWebpVariant;
use Spatie\Translatable\HasTranslations;

class Image extends Model
{
    use GeneratesUuids;
    use HasConversions;
    use HandlesUploads;
    use HasTranslations;
    use HasResponsiveVariants;
    use HasWebpVariant;

    protected $fillable = [
        'uuid',
        'disk',
        'mime_type',
        'file_extension',
        'width',
        'height',
        'size',
        'title',
        'alt',
    ];

    protected $casts = [
        'title' => 'json',
        'alt' => 'json',
    ];

    public function getTranslatableAttributes() : array
    {
        if (config('image-library.spatie_translatable')) {
            return ['title', 'alt'];
        }

        return [];
    }

    public function getShortPath(bool $includeFileExtension = true) : ?string
    {
        $basePath = $this->getBasePath();

        if (is_null($basePath)) {
            return null;
        }

        $path = $this->getBasePath() . '/' . $this->file_name;

        if ($includeFileExtension) {
            $path .= '.' . $this->file_extension;
        }

        return $path;
    }

    public function getBasePath() : ?string
    {
        return $this->uuid;
    }

    public function createBasePath() : void
    {
        Storage::disk($this->disk)->makeDirectory($this->getBasePath());
    }

    public function getPath() : string
    {
        $path = $this->getShortPath();

        if (is_null($path)) {
            return '';
        }

        return Storage::disk($this->disk)->path($this->getShortPath());
    }

    public function getUrl() : string
    {
        $path = $this->getShortPath();

        if (is_null($path)) {
            return '';
        }

        return Storage::disk($this->disk)->url($this->getShortPath());
    }

    protected function fileName() : Attribute
    {
        return Attribute::make(
            get: fn () => 'original',
        );
    }
}
