<?php

namespace Outerweb\ImageLibrary\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Outerweb\ImageLibrary\Models\Traits\HandlesConversions;
use Outerweb\ImageLibrary\Models\Traits\HasResponsiveVariants;
use Outerweb\ImageLibrary\Models\Traits\HasWebpVariant;

class ImageConversion extends Model
{
    use HasWebpVariant;
    use HandlesConversions;
    use HasResponsiveVariants;
    use HasWebpVariant;

    protected $fillable = [
        'image_id',
        'conversion_name',
        'conversion_md5',
        'width',
        'height',
        'size',
        'x',
        'y',
        'rotate',
        'scale_x',
        'scale_y',
    ];

    public function image(): BelongsTo
    {
        return $this->belongsTo(config('image-library.models.image'));
    }

    public function getShortPath(bool $includeFileExtension = true): string
    {
        $path = $this->getBasePath() . '/' . $this->file_name;

        if ($includeFileExtension) {
            $path .= '.' . $this->image->file_extension;
        }

        return $path;
    }

    public function getBasePath(): string
    {
        return $this->image->getBasePath();
    }

    public function getPath(): string
    {
        return Storage::disk($this->disk)->path($this->getShortPath());
    }

    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->getShortPath());
    }

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->getShortPath());
    }

    protected function fileName(): Attribute
    {
        return Attribute::make(
            get: fn() => Str::slug(Str::replace(':', '-', $this->conversion_name), separator: '-'),
        );
    }

    protected function disk(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image->disk,
        );
    }

    protected function fileExtension(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->image->file_extension,
        );
    }
}
