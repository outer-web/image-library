<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Entities;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Outerweb\ImageLibrary\Contracts\ConfiguresBreakpoints;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use ReflectionFunction;
use ReflectionParameter;
use Spatie\Image\Enums\CropPosition;

class ImageContext
{
    protected string $key;

    protected string|Closure|null $label = null;

    /** @var array<AspectRatio> */
    protected array $aspectRatioByBreakpoint = [];

    /** @var array<int|array<string, int>> */
    protected array $minWidthByBreakpoint = [];

    /** @var array<int|array<string, int>> */
    protected array $maxWidthByBreakpoint = [];

    /** @var array<CropPosition> */
    protected array $cropPositionByBreakpoint = [];

    /** @var array<int> */
    protected array $blurByBreakpoint = [];

    /** @var array<bool> */
    protected array $greyscaleByBreakpoint = [];

    /** @var array<bool> */
    protected array $sepiaByBreakpoint = [];

    protected bool $allowsMultiple = false;

    protected ?bool $generateWebP = null;

    protected ?bool $generateResponsiveVersions = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getConfigurationHash(): string
    {
        return md5(json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }

    public function label(string|Closure|null $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): ?string
    {
        return blank($this->label)
            ? Str::title(str_replace('_', ' ', $this->key))
            : $this->evaluate($this->label);
    }

    /** @param AspectRatio|array<string, AspectRatio> $aspectRatio */
    public function aspectRatio(AspectRatio|array $aspectRatio): self
    {
        $this->aspectRatioByBreakpoint = $this->getBreakpoints()
            ->mapWithKeys(function (ConfiguresBreakpoints $breakpoint) use ($aspectRatio) {
                if (is_array($aspectRatio)) {
                    if (! array_key_exists($breakpoint->value, $aspectRatio)) {
                        throw new InvalidArgumentException("Aspect ratio for breakpoint '{$breakpoint->value}' is not defined for ImageContext with key '{$this->key}'.");
                    }

                    return [$breakpoint->value => $aspectRatio[$breakpoint->value]];
                }

                return [$breakpoint->value => $aspectRatio];
            })
            ->all();

        return $this;
    }

    public function aspectRatioForBreakpoint(ConfiguresBreakpoints $breakpoint, AspectRatio $aspectRatio): self
    {
        $this->aspectRatioByBreakpoint[$breakpoint->value] = $aspectRatio;

        return $this;
    }

    public function aspectRatioFromBreakpoint(ConfiguresBreakpoints $breakpoint, AspectRatio $aspectRatio): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice($index)
            ->each(function (ConfiguresBreakpoints $bp) use ($aspectRatio) {
                $this->aspectRatioByBreakpoint[$bp->value] = $aspectRatio;
            });

        return $this;
    }

    public function aspectRatioToBreakpoint(ConfiguresBreakpoints $breakpoint, AspectRatio $aspectRatio): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice(0, $index + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($aspectRatio) {
                $this->aspectRatioByBreakpoint[$bp->value] = $aspectRatio;
            });

        return $this;
    }

    public function aspectRatioBetweenBreakpoints(ConfiguresBreakpoints $startBreakpoint, ConfiguresBreakpoints $endBreakpoint, AspectRatio $aspectRatio): self
    {
        $breakpoints = $this->getBreakpoints();

        $startIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($startBreakpoint) {
            return $bp->value === $startBreakpoint->value;
        });

        $endIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($endBreakpoint) {
            return $bp->value === $endBreakpoint->value;
        });

        $breakpoints
            ->slice($startIndex, $endIndex - $startIndex + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($aspectRatio) {
                $this->aspectRatioByBreakpoint[$bp->value] = $aspectRatio;
            });

        return $this;
    }

    /** @return array<string, AspectRatio> */
    public function getAspectRatioByBreakpoint(): array
    {
        return $this->aspectRatioByBreakpoint;
    }

    public function getAspectRatioForBreakpoint(ConfiguresBreakpoints $breakpoint): ?AspectRatio
    {
        return $this->aspectRatioByBreakpoint[$breakpoint->value] ?? null;
    }

    /** @param int|array<string, int> $minWidth */
    public function minWidth(int|array $minWidth): self
    {
        $this->minWidthByBreakpoint = $this->getBreakpoints()
            ->mapWithKeys(function (ConfiguresBreakpoints $breakpoint) use ($minWidth) {
                if (is_array($minWidth)) {
                    if (! array_key_exists($breakpoint->value, $minWidth)) {
                        throw new InvalidArgumentException("Min width for breakpoint '{$breakpoint->value}' is not defined for ImageContext with key '{$this->key}'.");
                    }

                    return [$breakpoint->value => $minWidth[$breakpoint->value]];
                }

                return [$breakpoint->value => $minWidth];
            })
            ->all();

        return $this;
    }

    public function minWidthForBreakpoint(ConfiguresBreakpoints $breakpoint, int $minWidth): self
    {
        $this->minWidthByBreakpoint[$breakpoint->value] = $minWidth;

        return $this;
    }

    public function minWidthFromBreakpoint(ConfiguresBreakpoints $breakpoint, int $minWidth): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice($index)
            ->each(function (ConfiguresBreakpoints $bp) use ($minWidth) {
                $this->minWidthByBreakpoint[$bp->value] = $minWidth;
            });

        return $this;
    }

    public function minWidthToBreakpoint(ConfiguresBreakpoints $breakpoint, int $minWidth): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice(0, $index + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($minWidth) {
                $this->minWidthByBreakpoint[$bp->value] = $minWidth;
            });

        return $this;
    }

    public function minWidthBetweenBreakpoints(ConfiguresBreakpoints $startBreakpoint, ConfiguresBreakpoints $endBreakpoint, int $minWidth): self
    {
        $breakpoints = $this->getBreakpoints();

        $startIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($startBreakpoint) {
            return $bp->value === $startBreakpoint->value;
        });

        $endIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($endBreakpoint) {
            return $bp->value === $endBreakpoint->value;
        });

        $breakpoints
            ->slice($startIndex, $endIndex - $startIndex + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($minWidth) {
                $this->minWidthByBreakpoint[$bp->value] = $minWidth;
            });

        return $this;
    }

    /** @return array<string, int> */
    public function getMinWidthByBreakpoint(): array
    {
        return $this->minWidthByBreakpoint;
    }

    public function getMinWidthForBreakpoint(ConfiguresBreakpoints $breakpoint): ?int
    {
        return $this->minWidthByBreakpoint[$breakpoint->value] ?? null;
    }

    /** @param int|array<string, int> $maxWidth */
    public function maxWidth(int|array $maxWidth): self
    {
        $this->maxWidthByBreakpoint = $this->getBreakpoints()
            ->mapWithKeys(function (ConfiguresBreakpoints $breakpoint) use ($maxWidth) {
                if (is_array($maxWidth)) {
                    if (! array_key_exists($breakpoint->value, $maxWidth)) {
                        throw new InvalidArgumentException("Max width for breakpoint '{$breakpoint->value}' is not defined for ImageContext with key '{$this->key}'.");
                    }
                }

                return [$breakpoint->value => is_array($maxWidth) ? $maxWidth[$breakpoint->value] : $maxWidth];
            })
            ->all();

        return $this;
    }

    public function maxWidthForBreakpoint(ConfiguresBreakpoints $breakpoint, int $maxWidth): self
    {
        $this->maxWidthByBreakpoint[$breakpoint->value] = $maxWidth;

        return $this;
    }

    public function maxWidthFromBreakpoint(ConfiguresBreakpoints $breakpoint, int $maxWidth): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice($index)
            ->each(function (ConfiguresBreakpoints $bp) use ($maxWidth) {
                $this->maxWidthByBreakpoint[$bp->value] = $maxWidth;
            });

        return $this;
    }

    public function maxWidthToBreakpoint(ConfiguresBreakpoints $breakpoint, int $maxWidth): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice(0, $index + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($maxWidth) {
                $this->maxWidthByBreakpoint[$bp->value] = $maxWidth;
            });

        return $this;
    }

    public function maxWidthBetweenBreakpoints(ConfiguresBreakpoints $startBreakpoint, ConfiguresBreakpoints $endBreakpoint, int $maxWidth): self
    {
        $breakpoints = $this->getBreakpoints();

        $startIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($startBreakpoint) {
            return $bp->value === $startBreakpoint->value;
        });

        $endIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($endBreakpoint) {
            return $bp->value === $endBreakpoint->value;
        });

        $breakpoints
            ->slice($startIndex, $endIndex - $startIndex + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($maxWidth) {
                $this->maxWidthByBreakpoint[$bp->value] = $maxWidth;
            });

        return $this;
    }

    /** @return array<string, int> */
    public function getMaxWidthByBreakpoint(): array
    {
        return $this->maxWidthByBreakpoint;
    }

    public function getMaxWidthForBreakpoint(ConfiguresBreakpoints $breakpoint): ?int
    {
        return $this->maxWidthByBreakpoint[$breakpoint->value] ?? null;
    }

    /** @param CropPosition|string|array<string, CropPosition|string> $cropPosition */
    public function cropPosition(CropPosition|string|array $cropPosition): self
    {
        $this->cropPositionByBreakpoint = $this->getBreakpoints()
            ->mapWithKeys(function (ConfiguresBreakpoints $breakpoint) use ($cropPosition) {
                if (is_array($cropPosition)) {
                    if (! array_key_exists($breakpoint->value, $cropPosition)) {
                        throw new InvalidArgumentException("Crop position for breakpoint '{$breakpoint->value}' is not defined for ImageContext with key '{$this->key}'.");
                    }

                    $cropPosition = $cropPosition[$breakpoint->value];
                    $cropPosition = $cropPosition instanceof CropPosition ? $cropPosition : CropPosition::from($cropPosition);

                    return [$breakpoint->value => $cropPosition];
                }

                $cropPosition = $cropPosition instanceof CropPosition ? $cropPosition : CropPosition::from($cropPosition);

                return [$breakpoint->value => $cropPosition];
            })
            ->all();

        return $this;
    }

    public function cropPositionForBreakpoint(ConfiguresBreakpoints $breakpoint, CropPosition|string $cropPosition): self
    {
        $cropPosition = $cropPosition instanceof CropPosition ? $cropPosition : CropPosition::from($cropPosition);

        $this->cropPositionByBreakpoint[$breakpoint->value] = $cropPosition;

        return $this;
    }

    public function cropPositionFromBreakpoint(ConfiguresBreakpoints $breakpoint, CropPosition|string $cropPosition): self
    {
        $cropPosition = $cropPosition instanceof CropPosition ? $cropPosition : CropPosition::from($cropPosition);

        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice($index)
            ->each(function (ConfiguresBreakpoints $bp) use ($cropPosition) {
                $this->cropPositionByBreakpoint[$bp->value] = $cropPosition;
            });

        return $this;
    }

    public function cropPositionToBreakpoint(ConfiguresBreakpoints $breakpoint, CropPosition|string $cropPosition): self
    {
        $cropPosition = $cropPosition instanceof CropPosition ? $cropPosition : CropPosition::from($cropPosition);

        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice(0, $index + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($cropPosition) {
                $this->cropPositionByBreakpoint[$bp->value] = $cropPosition;
            });

        return $this;
    }

    public function cropPositionBetweenBreakpoints(ConfiguresBreakpoints $startBreakpoint, ConfiguresBreakpoints $endBreakpoint, CropPosition|string $cropPosition): self
    {
        $cropPosition = $cropPosition instanceof CropPosition ? $cropPosition : CropPosition::from($cropPosition);

        $breakpoints = $this->getBreakpoints();

        $startIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($startBreakpoint) {
            return $bp->value === $startBreakpoint->value;
        });

        $endIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($endBreakpoint) {
            return $bp->value === $endBreakpoint->value;
        });

        $breakpoints
            ->slice($startIndex, $endIndex - $startIndex + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($cropPosition) {
                $this->cropPositionByBreakpoint[$bp->value] = $cropPosition;
            });

        return $this;
    }

    /** @return array<string, CropPosition> */
    public function getCropPositionByBreakpoint(): array
    {
        return $this->cropPositionByBreakpoint;
    }

    public function getCropPositionForBreakpoint(ConfiguresBreakpoints $breakpoint): ?CropPosition
    {
        return $this->cropPositionByBreakpoint[$breakpoint->value] ?? ImageLibrary::getDefaultCropPosition();
    }

    /** @param int|array<string, int> $blur */
    public function blur(int|array $blur): self
    {
        $this->blurByBreakpoint = $this->getBreakpoints()
            ->mapWithKeys(function (ConfiguresBreakpoints $breakpoint) use ($blur) {
                if (is_array($blur)) {
                    if (! array_key_exists($breakpoint->value, $blur)) {
                        throw new InvalidArgumentException("Blur for breakpoint '{$breakpoint->value}' is not defined for ImageContext with key '{$this->key}'.");
                    }

                    $blurValue = $blur[$breakpoint->value];

                    $this->validateBlurValue($blurValue, $breakpoint);

                    return [$breakpoint->value => $blurValue];
                }

                $this->validateBlurValue($blur);

                return [$breakpoint->value => $blur];
            })
            ->all();

        return $this;
    }

    public function validateBlurValue(mixed $blur, ?ConfiguresBreakpoints $breakpoint = null): void
    {
        if (! is_int($blur)) {
            throw new InvalidArgumentException(
                $breakpoint
                    ? "Blur value for breakpoint '{$breakpoint->value}' must be an integer for ImageContext with key '{$this->key}'."
                    : "Blur value must be an integer for ImageContext with key '{$this->key}'."
            );
        }

        if ($blur < 0 || $blur > 100) {
            throw new InvalidArgumentException(
                $breakpoint
                    ? "Blur value for breakpoint '{$breakpoint->value}' must be between 0 and 100 for ImageContext with key '{$this->key}'."
                    : "Blur value must be between 0 and 100 for ImageContext with key '{$this->key}'."
            );
        }
    }

    public function blurForBreakpoint(ConfiguresBreakpoints $breakpoint, int $blur): self
    {
        $this->validateBlurValue($blur, $breakpoint);

        $this->blurByBreakpoint[$breakpoint->value] = $blur;

        return $this;
    }

    public function blurFromBreakpoint(ConfiguresBreakpoints $breakpoint, int $blur): self
    {
        $this->validateBlurValue($blur, $breakpoint);

        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice($index)
            ->each(function (ConfiguresBreakpoints $bp) use ($blur) {
                $this->blurByBreakpoint[$bp->value] = $blur;
            });

        return $this;
    }

    public function blurToBreakpoint(ConfiguresBreakpoints $breakpoint, int $blur): self
    {
        $this->validateBlurValue($blur, $breakpoint);

        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice(0, $index + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($blur) {
                $this->blurByBreakpoint[$bp->value] = $blur;
            });

        return $this;
    }

    public function blurBetweenBreakpoints(ConfiguresBreakpoints $startBreakpoint, ConfiguresBreakpoints $endBreakpoint, int $blur): self
    {
        $this->validateBlurValue($blur);

        $breakpoints = $this->getBreakpoints();

        $startIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($startBreakpoint) {
            return $bp->value === $startBreakpoint->value;
        });

        $endIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($endBreakpoint) {
            return $bp->value === $endBreakpoint->value;
        });

        $breakpoints
            ->slice($startIndex, $endIndex - $startIndex + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($blur) {
                $this->blurByBreakpoint[$bp->value] = $blur;
            });

        return $this;
    }

    /** @return array<string, int> */
    public function getBlurByBreakpoint(): array
    {
        return $this->blurByBreakpoint;
    }

    public function getBlurForBreakpoint(ConfiguresBreakpoints $breakpoint): ?int
    {
        return $this->blurByBreakpoint[$breakpoint->value] ?? null;
    }

    /** @param bool|array<string, bool> $greyscale */
    public function greyscale(bool|array $greyscale = true): self
    {
        $this->greyscaleByBreakpoint = $this->getBreakpoints()
            ->mapWithKeys(function (ConfiguresBreakpoints $breakpoint) use ($greyscale) {
                if (is_array($greyscale)) {
                    if (! array_key_exists($breakpoint->value, $greyscale)) {
                        throw new InvalidArgumentException("Greyscale for breakpoint '{$breakpoint->value}' is not defined for ImageContext with key '{$this->key}'.");
                    }

                    $grayscaleValue = $greyscale[$breakpoint->value];

                    $this->validateGreyscaleValue($grayscaleValue, $breakpoint);

                    return [$breakpoint->value => $grayscaleValue];
                }

                return [$breakpoint->value => $greyscale];
            })
            ->all();

        return $this;
    }

    public function validateGreyscaleValue(mixed $greyscale, ?ConfiguresBreakpoints $breakpoint = null): void
    {
        if (! is_bool($greyscale)) {
            throw new InvalidArgumentException(
                $breakpoint
                    ? "Greyscale value for breakpoint '{$breakpoint->value}' must be a boolean for ImageContext with key '{$this->key}'."
                    : "Greyscale value must be a boolean for ImageContext with key '{$this->key}'."
            );
        }
    }

    /** @param bool|array<string, bool> $greyscale */
    public function grayscale(bool|array $greyscale = true): self
    {
        return $this->greyscale($greyscale);
    }

    public function greyscaleForBreakpoint(ConfiguresBreakpoints $breakpoint, bool $greyscale = true): self
    {
        $this->greyscaleByBreakpoint[$breakpoint->value] = $greyscale;

        return $this;
    }

    public function grayscaleForBreakpoint(ConfiguresBreakpoints $breakpoint, bool $greyscale = true): self
    {
        return $this->greyscaleForBreakpoint($breakpoint, $greyscale);
    }

    public function greyscaleFromBreakpoint(ConfiguresBreakpoints $breakpoint, bool $greyscale = true): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice($index)
            ->each(function (ConfiguresBreakpoints $bp) use ($greyscale) {
                $this->greyscaleByBreakpoint[$bp->value] = $greyscale;
            });

        return $this;
    }

    public function grayscaleFromBreakpoint(ConfiguresBreakpoints $breakpoint, bool $greyscale = true): self
    {
        return $this->greyscaleFromBreakpoint($breakpoint, $greyscale);
    }

    public function greyscaleToBreakpoint(ConfiguresBreakpoints $breakpoint, bool $greyscale = true): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice(0, $index + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($greyscale) {
                $this->greyscaleByBreakpoint[$bp->value] = $greyscale;
            });

        return $this;
    }

    public function grayscaleToBreakpoint(ConfiguresBreakpoints $breakpoint, bool $greyscale = true): self
    {
        return $this->greyscaleToBreakpoint($breakpoint, $greyscale);
    }

    public function greyscaleBetweenBreakpoints(ConfiguresBreakpoints $startBreakpoint, ConfiguresBreakpoints $endBreakpoint, bool $greyscale = true): self
    {
        $breakpoints = $this->getBreakpoints();

        $startIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($startBreakpoint) {
            return $bp->value === $startBreakpoint->value;
        });

        $endIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($endBreakpoint) {
            return $bp->value === $endBreakpoint->value;
        });

        $breakpoints
            ->slice($startIndex, $endIndex - $startIndex + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($greyscale) {
                $this->greyscaleByBreakpoint[$bp->value] = $greyscale;
            });

        return $this;
    }

    public function grayscaleBetweenBreakpoints(ConfiguresBreakpoints $startBreakpoint, ConfiguresBreakpoints $endBreakpoint, bool $greyscale = true): self
    {
        return $this->greyscaleBetweenBreakpoints($startBreakpoint, $endBreakpoint, $greyscale);
    }

    /** @return array<string, bool> */
    public function getGreyscaleByBreakpoint(): array
    {
        return $this->greyscaleByBreakpoint;
    }

    /** @return array<string, bool> */
    public function getGrayscaleByBreakpoint(): array
    {
        return $this->getGreyscaleByBreakpoint();
    }

    public function getGreyscaleForBreakpoint(ConfiguresBreakpoints $breakpoint): ?bool
    {
        return $this->greyscaleByBreakpoint[$breakpoint->value] ?? null;
    }

    public function getGrayscaleForBreakpoint(ConfiguresBreakpoints $breakpoint): ?bool
    {
        return $this->getGreyscaleForBreakpoint($breakpoint);
    }

    /** @param bool|array<string, bool> $sepia */
    public function sepia(bool|array $sepia = true): self
    {
        $this->sepiaByBreakpoint = $this->getBreakpoints()
            ->mapWithKeys(function (ConfiguresBreakpoints $breakpoint) use ($sepia) {
                if (is_array($sepia)) {
                    if (! array_key_exists($breakpoint->value, $sepia)) {
                        throw new InvalidArgumentException("Sepia for breakpoint '{$breakpoint->value}' is not defined for ImageContext with key '{$this->key}'.");
                    }

                    $sepiaValue = $sepia[$breakpoint->value];

                    $this->validateSepiaValue($sepiaValue, $breakpoint);

                    return [$breakpoint->value => $sepiaValue];
                }

                return [$breakpoint->value => $sepia];
            })
            ->all();

        return $this;
    }

    public function validateSepiaValue(mixed $sepia, ?ConfiguresBreakpoints $breakpoint = null): void
    {
        if (! is_bool($sepia)) {
            throw new InvalidArgumentException(
                $breakpoint
                    ? "Sepia value for breakpoint '{$breakpoint->value}' must be a boolean for ImageContext with key '{$this->key}'."
                    : "Sepia value must be a boolean for ImageContext with key '{$this->key}'."
            );
        }
    }

    public function sepiaForBreakpoint(ConfiguresBreakpoints $breakpoint, bool $sepia = true): self
    {
        $this->sepiaByBreakpoint[$breakpoint->value] = $sepia;

        return $this;
    }

    public function sepiaFromBreakpoint(ConfiguresBreakpoints $breakpoint, bool $sepia = true): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice($index)
            ->each(function (ConfiguresBreakpoints $bp) use ($sepia) {
                $this->sepiaByBreakpoint[$bp->value] = $sepia;
            });

        return $this;
    }

    public function sepiaToBreakpoint(ConfiguresBreakpoints $breakpoint, bool $sepia = true): self
    {
        $index = $this->getBreakpoints()
            ->search(function (ConfiguresBreakpoints $bp) use ($breakpoint) {
                return $bp->value === $breakpoint->value;
            });

        $this->getBreakpoints()
            ->slice(0, $index + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($sepia) {
                $this->sepiaByBreakpoint[$bp->value] = $sepia;
            });

        return $this;
    }

    public function sepiaBetweenBreakpoints(ConfiguresBreakpoints $startBreakpoint, ConfiguresBreakpoints $endBreakpoint, bool $sepia = true): self
    {
        $breakpoints = $this->getBreakpoints();

        $startIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($startBreakpoint) {
            return $bp->value === $startBreakpoint->value;
        });

        $endIndex = $breakpoints->search(function (ConfiguresBreakpoints $bp) use ($endBreakpoint) {
            return $bp->value === $endBreakpoint->value;
        });

        $breakpoints
            ->slice($startIndex, $endIndex - $startIndex + 1)
            ->each(function (ConfiguresBreakpoints $bp) use ($sepia) {
                $this->sepiaByBreakpoint[$bp->value] = $sepia;
            });

        return $this;
    }

    /** @return array<string, bool> */
    public function getSepiaByBreakpoint(): array
    {
        return $this->sepiaByBreakpoint;
    }

    public function getSepiaForBreakpoint(ConfiguresBreakpoints $breakpoint): ?bool
    {
        return $this->sepiaByBreakpoint[$breakpoint->value] ?? null;
    }

    public function allowsMultiple(bool $allowsMultiple = true): self
    {
        $this->allowsMultiple = $allowsMultiple;

        return $this;
    }

    public function getAllowsMultiple(): bool
    {
        return $this->allowsMultiple;
    }

    public function generateWebP(bool $generateWebP = true): self
    {
        $this->generateWebP = $generateWebP;

        return $this;
    }

    public function getGenerateWebP(): bool
    {
        return $this->generateWebP ?? ImageLibrary::shouldGenerateWebp();
    }

    public function generateResponsiveVersions(bool $generateResponsiveVersions = true): self
    {
        $this->generateResponsiveVersions = $generateResponsiveVersions;

        return $this;
    }

    public function getGenerateResponsiveVersions(): bool
    {
        return $this->generateResponsiveVersions ?? ImageLibrary::shouldGenerateResponsiveVersions();
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'aspectRatioByBreakpoint' => array_map(
                fn (AspectRatio $ar) => $ar->toArray(),
                $this->aspectRatioByBreakpoint
            ),
            'blurByBreakpoint' => $this->blurByBreakpoint,
            'greyscaleByBreakpoint' => $this->greyscaleByBreakpoint,
            'sepiaByBreakpoint' => $this->sepiaByBreakpoint,
            'allowsMultiple' => $this->allowsMultiple,
            'generateWebP' => $this->generateWebP,
            'generateResponsiveVersions' => $this->generateResponsiveVersions,
        ];
    }

    /**
     * @template T
     *
     * @param  T | callable(): T  $value
     * @return T
     */
    private function evaluate(mixed $value): mixed
    {
        if (! $value instanceof Closure) {
            return $value;
        }

        $dependencies = collect(new ReflectionFunction($value)->getParameters())
            ->map(function (ReflectionParameter $parameter) {
                return match ($parameter->getName()) {
                    'imageContext' => $this,
                    default => null,
                };
            })
            ->all();

        return $value(...$dependencies);
    }

    private function getBreakpoints(): Collection
    {
        return collect(ImageLibrary::getBreakpointEnum()::sortedCases());
    }
}
