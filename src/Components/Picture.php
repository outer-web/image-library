<?php

namespace Outerweb\ImageLibrary\Components;

use Closure;
use Illuminate\Contracts\View\View;

class Picture extends Image
{
    public function render(): View|Closure|string
    {
        return view('image-library::components.picture');
    }

    public function srcsetWebp(): string
    {
        $image = $this->imageConversion ?? $this->image;

        $responsiveVariants = $image->getResponsiveVariants(true);

        if ($responsiveVariants->isEmpty()) {
            return null;
        }

        return $responsiveVariants->map(function ($variant) {
            return "{$variant->url} {$variant->width}w";
        })->implode(', ');
    }
}
