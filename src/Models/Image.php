<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Models;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Outerweb\ImageLibrary\Contracts\ConfiguresBreakpoints;
use Outerweb\ImageLibrary\Database\Factories\ImageFactory;
use Outerweb\ImageLibrary\Entities\CropData;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Jobs\GenerateImageVersionJob;
use Outerweb\ImageLibrary\Jobs\GenerateResponsiveImageVersionsJob;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Translatable\HasTranslations;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property-read int $id
 * @property string $uuid
 * @property string $model_type
 * @property int $model_id
 * @property int $source_image_id
 * @property-read SourceImage $sourceImage
 * @property ImageContext|string $context
 * @property string $context_configuration_hash
 * @property int $sort_order
 * @property string $disk
 * @property array<string, CropData|null> $crop_data
 * @property array<string,string|null>|string|null $alt_text
 * @property array<string,mixed>|null $custom_properties
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 *
 * @psalm-property-write ImageContext|string $context
 * @psalm-property-write array<string,string|null>|null $alt_text
 *
 * @psalm-property-read ImageContext $context
 * @psalm-property-read string|null $alt_text
 *
 * @method static ImageFactory factory($count = null, $state = [])
 */
#[UseFactory(ImageFactory::class)]
class Image extends Model implements Sortable
{
    use HasFactory;
    use HasTranslations;
    use SortableTrait;

    public $sortable = [
        'order_column_name' => 'sort_order',
        'sort_when_creating' => true,
    ];

    public $translatable = [
        'alt_text',
    ];

    protected $fillable = [
        'uuid',
        'model_type',
        'model_id',
        'source_image_id',
        'context',
        'context_configuration_hash',
        'sort_order',
        'disk',
        'crop_data',
        'alt_text',
        'custom_properties',
    ];

    protected $attributes = [
        'custom_properties' => '{}',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function sourceImage(): BelongsTo
    {
        return $this->belongsTo(ImageLibrary::getSourceImageModel());
    }

    public function buildSortQuery(): Builder
    {
        return static::query()
            ->where('model_type', $this->model_type)
            ->where('model_id', $this->model_id)
            ->where('context', $this->context->getKey());
    }

    public function generate(): void
    {
        $this->deleteFiles();

        Bus::chain([
            Bus::batch(
                collect(ImageLibrary::getBreakpointEnum()::sortedCases())
                    ->map(function (ConfiguresBreakpoints $breakpoint) {
                        return new GenerateImageVersionJob($this->getKey(), $breakpoint);
                    })
                    ->all()
            ),
            Bus::batch(
                collect(ImageLibrary::getBreakpointEnum()::sortedCases())
                    ->map(function (ConfiguresBreakpoints $breakpoint) {
                        return new GenerateResponsiveImageVersionsJob($this->getKey(), $breakpoint);
                    })
                    ->all()
            ),
        ])
            ->onConnection(ImageLibrary::getDefaultQueueConnection())
            ->onQueue(ImageLibrary::getDefaultQueue())
            ->dispatch();
    }

    public function getRelativeBasePath(): string
    {
        return $this->sourceImage->getRelativeBasePath().'/'.$this->uuid;
    }

    public function getAbsoluteBasePath(): string
    {
        return Storage::disk($this->disk)->path($this->getRelativeBasePath());
    }

    public function getRelativePathForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): string
    {
        $extension ??= $this->sourceImage->extension;

        return $this->getRelativeBasePath().'/'.urlencode($breakpoint->getSlug()).'.'.$extension;
    }

    public function getAbsolutePathForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): string
    {
        return Storage::disk($this->disk)->path($this->getRelativePathForBreakpoint($breakpoint, $extension));
    }

    public function getResponsiveRelativePathsForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): Collection
    {
        $extension ??= $this->sourceImage->extension;

        $files = Storage::disk($this->disk)->files($this->getRelativeBasePath());

        return collect($files)
            ->filter(function (string $file) use ($breakpoint, $extension): bool {
                return (
                    Str::startsWith($file, $this->getRelativeBasePath().'/'.urlencode($breakpoint->getSlug()).'_w')
                    || $file === $this->getRelativePathForBreakpoint($breakpoint, $extension)
                )
                    && Str::endsWith($file, '.'.$extension);
            })
            ->values();
    }

    public function getResponsiveAbsolutePathsForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): Collection
    {
        return collect($this->getResponsiveRelativePathsForBreakpoint($breakpoint, $extension))
            ->map(function (string $path): string {
                return Storage::disk($this->disk)->path($path);
            });
    }

    public function getForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): ?string
    {
        return Storage::disk($this->disk)->get($this->getRelativePathForBreakpoint($breakpoint, $extension));
    }

    public function existsForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): bool
    {
        return Storage::disk($this->disk)->exists($this->getRelativePathForBreakpoint($breakpoint, $extension));
    }

    public function missingForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): bool
    {
        return Storage::disk($this->disk)->missing($this->getRelativePathForBreakpoint($breakpoint, $extension));
    }

    public function downloadForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): StreamedResponse
    {
        return Storage::disk($this->disk)->download($this->getRelativePathForBreakpoint($breakpoint, $extension));
    }

    public function urlForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): string
    {
        $path = $this->getRelativePathForBreakpoint($breakpoint, $extension);

        if (ImageLibrary::shouldUseTemporaryUrlsForDisk($this->disk)) {
            return $this->temporaryUrlForRelativePath($path);
        }

        return $this->urlForRelativePath($path);
    }

    public function urlForRelativePath(string $relativePath): string
    {
        if (ImageLibrary::shouldUseTemporaryUrlsForDisk($this->disk)) {
            return $this->temporaryUrlForRelativePath($relativePath);
        }

        return Storage::disk($this->disk)->url($relativePath);
    }

    public function temporaryUrlForRelativePath(string $relativePath, ?CarbonInterface $expiration = null, array $options = []): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $relativePath,
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

            $model->updateContextConfigurationHash();
        });

        static::created(function (self $model) {
            $model->generate();
        });

        static::updated(function (self $model) {
            if ($model->wasChanged(['context_configuration_hash', 'crop_data'])) {
                $model->generate();
            }
        });

        static::deleting(function (self $model) {
            $model->deleteFiles();
        });
    }

    /** @return Attribute<string, string> */
    protected function disk(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? $this->sourceImage->disk,
            set: fn (?string $value): string => $value ?? $this->sourceImage->disk,
        );
    }

    /** @return Attribute<ImageContext|null, string> */
    protected function context(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?ImageContext => ImageLibrary::getImageContextByKey($value),
            set: fn (ImageContext|string $value): string => is_string($value) ? $value : $value->getKey(),
        );
    }

    /** @return Attribute<array<string, CropData>, string> */
    protected function cropData(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): array {
                if (blank($value)) {
                    return $this->generateCropData(null);
                }

                $data = json_decode($value, true);

                if (! is_array($data)) {
                    return $this->generateCropData(null);
                }

                return $this->generateCropData($data);
            },
            set: function (array|CropData|null $value): string {
                return json_encode($this->generateCropData($value));
            },
        );
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

    private function updateContextConfigurationHash(): void
    {
        $this->context_configuration_hash = $this->context->getConfigurationHash();
    }

    /**
     * @param  array<string, mixed>|null|CropData  $cropData
     * @return array<string, CropData|null>
     */
    private function generateCropData(array|CropData|null $cropData): array
    {
        if ($cropData instanceof CropData || is_null($cropData)) {
            return collect(ImageLibrary::getBreakpointEnum()::sortedCases())
                ->mapWithKeys(function (BackedEnum $case) use ($cropData): array {
                    return [$case->value => $cropData];
                })
                ->all();
        }

        return collect(ImageLibrary::getBreakpointEnum()::sortedCases())
            ->mapWithKeys(function (BackedEnum $case) use ($cropData): array {
                $data = $cropData[$case->value] ?? null;

                if (
                    is_null($data)
                    || ! is_array($data)
                    || ! array_key_exists('width', $data)
                    || ! array_key_exists('height', $data)
                ) {
                    return [$case->value => null];
                }

                return [$case->value => CropData::make(
                    $data['width'],
                    $data['height'],
                    $data['x'] ?? null,
                    $data['y'] ?? null,
                )];
            })
            ->all();
    }
}
