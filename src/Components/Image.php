<?php

namespace Outerweb\ImageLibrary\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Outerweb\ImageLibrary\Models\Image as ModelsImage;
use Outerweb\ImageLibrary\Models\ImageConversion;

class Image extends Component
{
    public ?string $src = null;

    public ?ImageConversion $imageConversion = null;

    public ?ImageConversion $fallbackImageConversion = null;

    public function __construct(
        public ?ModelsImage $image,
        public ?string $title = null,
        public ?string $alt = null,
        public ?string $conversion = null,
        public string|ModelsImage|null $fallback = null,
        public ?string $fallbackConversion = null,
    ) {
        $this->title = $this->title ?? $this->image->title ?? null;
        $this->alt = $this->alt ?? $this->image->alt ?? null;
    }

    public function render(): View|Closure|string
    {
        return view('image-library::components.image');
    }

    public function shouldRender(): bool
    {
        if (is_null($this->src())) {
            return false;
        }

        return true;
    }

    public function src(): ?string
    {
        if ($this->src) {
            return $this->src;
        }

        if ($this->conversion) {
            $conversion = $this->getConversion();

            if ($conversion) {
                return $this->src = $conversion->getUrl();
            }

            if (is_null($this->fallback) && $this->fallbackConversion) {
                $conversion = $this->getFallbackConversion();

                if ($conversion) {
                    $this->imageConversion = $conversion;

                    return $this->src = $conversion->getUrl();
                }
            }
        }

        $src = $this->image?->getUrl();

        if ($src) {
            return $this->src = $src;
        }

        return $this->src = $this->getSrcByFallback();
    }

    public function srcset(): ?string
    {
        $image = $this->imageConversion ?? $this->image;

        if (is_null($image)) {
            return null;
        }

        $responsiveVariants = $image->getResponsiveVariants();

        if ($responsiveVariants->isEmpty()) {
            return null;
        }

        return $responsiveVariants->map(function ($variant) {
            return "{$variant->url} {$variant->width}w";
        })->implode(', ');
    }

    public function width(): ?int
    {
        return $this->imageConversion?->width ?? $this->image?->width;
    }

    public function height(): ?int
    {
        return $this->imageConversion?->height ?? $this->image?->height;
    }

    public function getConversion(): ?ImageConversion
    {
        if ($this->imageConversion) {
            return $this->imageConversion;
        }

        return $this->imageConversion = $this->image?->getConversion($this->conversion);
    }

    public function getFallbackConversion(): ?ImageConversion
    {
        if ($this->fallbackImageConversion) {
            return $this->fallbackImageConversion;
        }

        return $this->fallbackImageConversion = $this->image?->getConversion($this->fallbackConversion);
    }

    public function getSrcByFallback(): ?string
    {
        if ($this->fallback instanceof ModelsImage) {
            $this->image = $this->fallback;

            if ($this->getFallbackConversion()) {
                $this->conversion = $this->fallbackConversion;
            }

            $this->imageConversion = null;
            $this->fallback = null;
            $this->fallbackImageConversion = null;

            return $this->src();
        }

        return $this->fallback;
    }
}
