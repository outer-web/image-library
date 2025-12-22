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

    protected ?bool $useBreakpoints = null;

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
        if (! $this->getUseBreakpoints()) {
            if (is_array($aspectRatio)) {
                throw new InvalidArgumentException("Aspect ratio must be an instance of AspectRatio when breakpoints are disabled for ImageContext with key '{$this->key}'.");
            }

            $this->aspectRatioByBreakpoint = [
                'default' => $aspectRatio,
            ];

            return $this;
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set aspect ratio for breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        $this->aspectRatioByBreakpoint[$breakpoint->value] = $aspectRatio;

        return $this;
    }

    public function aspectRatioFromBreakpoint(ConfiguresBreakpoints $breakpoint, AspectRatio $aspectRatio): self
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set aspect ratio from breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set aspect ratio to breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set aspect ratio between breakpoints when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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

    public function getAspectRatio(?ConfiguresBreakpoints $breakpoint = null): ?AspectRatio
    {
        if (! $this->getUseBreakpoints()) {
            return $this->aspectRatioByBreakpoint['default'] ?? null;
        }

        if (is_null($breakpoint)) {
            throw new InvalidArgumentException("Breakpoint is required when breakpoints are enabled for ImageContext with key '{$this->key}'.");
        }

        return $this->aspectRatioByBreakpoint[$breakpoint->value] ?? null;
    }

    /** @return array<string, AspectRatio> */
    public function getAspectRatioByBreakpoint(): array
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot get aspect ratios by breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        return $this->aspectRatioByBreakpoint;
    }

    /** @param int|array<string, int> $minWidth */
    public function minWidth(int|array $minWidth): self
    {
        if (! $this->getUseBreakpoints()) {
            if (is_array($minWidth)) {
                throw new InvalidArgumentException("Min width must be an integer when breakpoints are disabled for ImageContext with key '{$this->key}'.");
            }

            $this->minWidthByBreakpoint = [
                'default' => $minWidth,
            ];

            return $this;
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set min width for breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        $this->minWidthByBreakpoint[$breakpoint->value] = $minWidth;

        return $this;
    }

    public function minWidthFromBreakpoint(ConfiguresBreakpoints $breakpoint, int $minWidth): self
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set min width from breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set min width to breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set min width between breakpoints when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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

    public function getMinWidth(?ConfiguresBreakpoints $breakpoint = null): ?int
    {
        if (! $this->getUseBreakpoints()) {
            return $this->minWidthByBreakpoint['default'] ?? null;
        }

        if (is_null($breakpoint)) {
            throw new InvalidArgumentException("Breakpoint is required when breakpoints are enabled for ImageContext with key '{$this->key}'.");
        }

        return $this->minWidthByBreakpoint[$breakpoint->value] ?? null;
    }

    /** @return array<string, int> */
    public function getMinWidthByBreakpoint(): array
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot get min widths by breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        return $this->minWidthByBreakpoint;
    }

    /** @param int|array<string, int> $maxWidth */
    public function maxWidth(int|array $maxWidth): self
    {
        if (! $this->getUseBreakpoints()) {
            if (is_array($maxWidth)) {
                throw new InvalidArgumentException("Max width must be an integer when breakpoints are disabled for ImageContext with key '{$this->key}'.");
            }

            $this->maxWidthByBreakpoint = [
                'default' => $maxWidth,
            ];

            return $this;
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set max width for breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        $this->maxWidthByBreakpoint[$breakpoint->value] = $maxWidth;

        return $this;
    }

    public function maxWidthFromBreakpoint(ConfiguresBreakpoints $breakpoint, int $maxWidth): self
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set max width from breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set max width to breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set max width between breakpoints when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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

    public function getMaxWidth(?ConfiguresBreakpoints $breakpoint = null): ?int
    {
        if (! $this->getUseBreakpoints()) {
            return $this->maxWidthByBreakpoint['default'] ?? null;
        }

        if (is_null($breakpoint)) {
            throw new InvalidArgumentException("Breakpoint is required when breakpoints are enabled for ImageContext with key '{$this->key}'.");
        }

        return $this->maxWidthByBreakpoint[$breakpoint->value] ?? null;
    }

    /** @return array<string, int> */
    public function getMaxWidthByBreakpoint(): array
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot get max widths by breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        return $this->maxWidthByBreakpoint;
    }

    /** @param CropPosition|string|array<string, CropPosition|string> $cropPosition */
    public function cropPosition(CropPosition|string|array $cropPosition): self
    {
        if (! $this->getUseBreakpoints()) {
            if (is_array($cropPosition)) {
                throw new InvalidArgumentException("Crop position must be an instance of CropPosition or string when breakpoints are disabled for ImageContext with key '{$this->key}'.");
            }

            $cropPosition = $cropPosition instanceof CropPosition ? $cropPosition : CropPosition::from($cropPosition);

            $this->cropPositionByBreakpoint = [
                'default' => $cropPosition,
            ];

            return $this;
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set crop position for breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        $cropPosition = $cropPosition instanceof CropPosition ? $cropPosition : CropPosition::from($cropPosition);

        $this->cropPositionByBreakpoint[$breakpoint->value] = $cropPosition;

        return $this;
    }

    public function cropPositionFromBreakpoint(ConfiguresBreakpoints $breakpoint, CropPosition|string $cropPosition): self
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set crop position from breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set crop position to breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set crop position between breakpoints when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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

    public function getCropPosition(?ConfiguresBreakpoints $breakpoint = null): ?CropPosition
    {
        if (! $this->getUseBreakpoints()) {
            return $this->cropPositionByBreakpoint['default'] ?? ImageLibrary::getDefaultCropPosition();
        }

        if (is_null($breakpoint)) {
            throw new InvalidArgumentException("Breakpoint is required when breakpoints are enabled for ImageContext with key '{$this->key}'.");
        }

        return $this->cropPositionByBreakpoint[$breakpoint->value] ?? ImageLibrary::getDefaultCropPosition();
    }

    /** @return array<string, CropPosition> */
    public function getCropPositionByBreakpoint(): array
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot get crop positions by breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        return $this->cropPositionByBreakpoint;
    }

    /** @param int|array<string, int> $blur */
    public function blur(int|array $blur): self
    {
        if (! $this->getUseBreakpoints()) {
            if (is_array($blur)) {
                throw new InvalidArgumentException("Blur must be an integer when breakpoints are disabled for ImageContext with key '{$this->key}'.");
            }

            $this->validateBlurValue($blur);

            $this->blurByBreakpoint = [
                'default' => $blur,
            ];

            return $this;
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set blur for breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        $this->validateBlurValue($blur, $breakpoint);

        $this->blurByBreakpoint[$breakpoint->value] = $blur;

        return $this;
    }

    public function blurFromBreakpoint(ConfiguresBreakpoints $breakpoint, int $blur): self
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set blur from breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set blur to breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set blur between breakpoints when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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

    public function getBlur(?ConfiguresBreakpoints $breakpoint = null): ?int
    {
        if (! $this->getUseBreakpoints()) {
            return $this->blurByBreakpoint['default'] ?? null;
        }

        if (is_null($breakpoint)) {
            throw new InvalidArgumentException("Breakpoint is required when breakpoints are enabled for ImageContext with key '{$this->key}'.");
        }

        return $this->blurByBreakpoint[$breakpoint->value] ?? null;
    }

    /** @return array<string, int> */
    public function getBlurByBreakpoint(): array
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot get blur values by breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        return $this->blurByBreakpoint;
    }

    /** @param bool|array<string, bool> $greyscale */
    public function greyscale(bool|array $greyscale = true): self
    {
        if (! $this->getUseBreakpoints()) {
            if (is_array($greyscale)) {
                throw new InvalidArgumentException("Greyscale must be a boolean when breakpoints are disabled for ImageContext with key '{$this->key}'.");
            }

            $this->validateGreyscaleValue($greyscale);

            $this->greyscaleByBreakpoint = [
                'default' => $greyscale,
            ];

            return $this;
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set greyscale for breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        $this->greyscaleByBreakpoint[$breakpoint->value] = $greyscale;

        return $this;
    }

    public function grayscaleForBreakpoint(ConfiguresBreakpoints $breakpoint, bool $greyscale = true): self
    {
        return $this->greyscaleForBreakpoint($breakpoint, $greyscale);
    }

    public function greyscaleFromBreakpoint(ConfiguresBreakpoints $breakpoint, bool $greyscale = true): self
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set greyscale from breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set greyscale to breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set greyscale between breakpoints when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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

    public function getGreyscale(?ConfiguresBreakpoints $breakpoint = null): ?bool
    {
        if (! $this->getUseBreakpoints()) {
            return $this->greyscaleByBreakpoint['default'] ?? null;
        }

        if (is_null($breakpoint)) {
            throw new InvalidArgumentException("Breakpoint is required when breakpoints are enabled for ImageContext with key '{$this->key}'.");
        }

        return $this->greyscaleByBreakpoint[$breakpoint->value] ?? null;
    }

    public function getGrayscale(?ConfiguresBreakpoints $breakpoint = null): ?bool
    {
        return $this->getGreyscale($breakpoint);
    }

    /** @return array<string, bool> */
    public function getGreyscaleByBreakpoint(): array
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot get greyscale values by breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        return $this->greyscaleByBreakpoint;
    }

    /** @return array<string, bool> */
    public function getGrayscaleByBreakpoint(): array
    {
        return $this->getGreyscaleByBreakpoint();
    }

    /** @param bool|array<string, bool> $sepia */
    public function sepia(bool|array $sepia = true): self
    {
        if (! $this->getUseBreakpoints()) {
            if (is_array($sepia)) {
                throw new InvalidArgumentException("Sepia must be a boolean when breakpoints are disabled for ImageContext with key '{$this->key}'.");
            }

            $this->validateSepiaValue($sepia);

            $this->sepiaByBreakpoint = [
                'default' => $sepia,
            ];

            return $this;
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set sepia for breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        $this->sepiaByBreakpoint[$breakpoint->value] = $sepia;

        return $this;
    }

    public function sepiaFromBreakpoint(ConfiguresBreakpoints $breakpoint, bool $sepia = true): self
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set sepia from breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set sepia to breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot set sepia between breakpoints when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

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

    public function getSepia(?ConfiguresBreakpoints $breakpoint = null): ?bool
    {
        if (! $this->getUseBreakpoints()) {
            return $this->sepiaByBreakpoint['default'] ?? null;
        }

        if (is_null($breakpoint)) {
            throw new InvalidArgumentException("Breakpoint is required when breakpoints are enabled for ImageContext with key '{$this->key}'.");
        }

        return $this->sepiaByBreakpoint[$breakpoint->value] ?? null;
    }

    /** @return array<string, bool> */
    public function getSepiaByBreakpoint(): array
    {
        if (! $this->getUseBreakpoints()) {
            throw new InvalidArgumentException("Cannot get sepia values by breakpoint when breakpoints are disabled for ImageContext with key '{$this->key}'.");
        }

        return $this->sepiaByBreakpoint;
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

    public function useBreakpoints(bool $useBreakpoints = true): self
    {
        $this->useBreakpoints = $useBreakpoints;

        return $this;
    }

    public function getUseBreakpoints(): bool
    {
        return $this->useBreakpoints ?? ImageLibrary::shouldUseBreakpoints();
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
            'aspectRatio' => $this->getUseBreakpoints()
                ? array_map(
                    fn (AspectRatio $ar) => $ar->toArray(),
                    $this->aspectRatioByBreakpoint
                )
                : ($this->aspectRatioByBreakpoint['default'] ?? null)?->toArray(),
            'blur' => $this->getUseBreakpoints()
                ? $this->blurByBreakpoint
                : ($this->blurByBreakpoint['default'] ?? null),
            'grayscale' => $this->getUseBreakpoints()
                ? $this->greyscaleByBreakpoint
                : ($this->greyscaleByBreakpoint['default'] ?? null),
            'sepia' => $this->getUseBreakpoints()
                ? $this->sepiaByBreakpoint
                : ($this->sepiaByBreakpoint['default'] ?? null),
            'allowsMultiple' => $this->allowsMultiple,
            'useBreakpoints' => $this->useBreakpoints,
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
