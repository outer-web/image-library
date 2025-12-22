<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Outerweb\ImageLibrary\Database\Factories\SourceImageFactory;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\Translatable\HasTranslations;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * @property-read int $id
 * @property string $uuid
 * @property string $disk
 * @property string $name
 * @property string $extension
 * @property-read string $name_with_extension
 * @property string $mime_type
 * @property int $width
 * @property int $height
 * @property int $size
 * @property array<string,string|null>|string|null $alt_text
 * @property array<string,mixed>|null $custom_properties
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Image> $images
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 *
 * @psalm-property-write array<string,string|null>|null $alt_text
 *
 * @psalm-property-read string|null $alt_text
 */
#[UseFactory(SourceImageFactory::class)]
class SourceImage extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = [
        'alt_text',
    ];

    protected $fillable = [
        'uuid',
        'disk',
        'name',
        'extension',
        'mime_type',
        'width',
        'height',
        'size',
        'alt_text',
        'custom_properties',
    ];

    protected $attributes = [
        'custom_properties' => '{}',
    ];

    public static function upload(UploadedFile $file, array $attributes = []): self
    {
        try {
            DB::beginTransaction();

            $temporaryPath = new TemporaryDirectory()->create()->path($file->getClientOriginalName());

            $optimizedFile = ImageLibrary::getSpatieImage()
                ->loadFile($file->getRealPath())
                ->optimize()
                ->save($temporaryPath);

            $model = static::query()
                ->create(array_merge(
                    [
                        'disk' => ImageLibrary::getDefaultDisk(),
                        'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    ],
                    $attributes,
                    [
                        'extension' => $file->getClientOriginalExtension(),
                        'mime_type' => $file->getClientMimeType(),
                        'width' => $optimizedFile->getWidth(),
                        'height' => $optimizedFile->getHeight(),
                        'size' => filesize($temporaryPath),
                    ]
                ));

            // Create directory
            Storage::disk($model->disk)->makeDirectory($model->getRelativeBasePath());

            $optimizedFile->save($model->getAbsolutePath());

            DB::commit();

            return $model;
        } catch (Throwable $exception) {
            DB::rollBack();

            if (isset($model)) {
                $model->deleteFiles();
            }

            throw $exception;
        }
    }

    public function images(): HasMany
    {
        return $this->hasMany(ImageLibrary::getImageModel());
    }

    public function getRelativeBasePath(): string
    {
        return ImageLibrary::getBasePath().'/'.$this->uuid;
    }

    public function getAbsoluteBasePath(): string
    {
        return Storage::disk($this->disk)->path($this->getRelativeBasePath());
    }

    public function getRelativePath(): string
    {
        return $this->getRelativeBasePath().'/original'.'.'.$this->extension;
    }

    public function getAbsolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->getRelativePath());
    }

    public function get(): ?string
    {
        return Storage::disk($this->disk)->get($this->getRelativePath());
    }

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->getRelativePath());
    }

    public function missing(): bool
    {
        return Storage::disk($this->disk)->missing($this->getRelativePath());
    }

    public function download(): StreamedResponse
    {
        return Storage::disk($this->disk)->download($this->getRelativePath());
    }

    public function url(): string
    {
        if (ImageLibrary::shouldUseTemporaryUrlsForDisk($this->disk)) {
            return $this->temporaryUrl();
        }

        return Storage::disk($this->disk)->url($this->getRelativePath());
    }

    public function temporaryUrl(?CarbonInterface $expiration = null, array $options = []): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $this->getRelativePath(),
            $expiration ?? now()->addMinutes(ImageLibrary::getTemporaryUrlExpirationMinutesForDisk($this->disk)),
            $options,
        );
    }

    public function deleteFiles(): void
    {
        Storage::disk($this->disk)->deleteDirectory($this->getRelativeBasePath());
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
        });

        static::deleting(function (self $model) {
            $model->deleteFiles();
        });
    }

    /**
     * @return array{
     *   alt_text: 'array',
     *   custom_properties: 'array',
     * }
     */
    protected function casts(): array
    {
        return [
            'alt_text' => 'array',
            'custom_properties' => 'array',
        ];
    }

    /** @return Attribute<non-falsy-string, never> */
    protected function nameWithExtension(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->name.'.'.$this->extension,
        );
    }
}
