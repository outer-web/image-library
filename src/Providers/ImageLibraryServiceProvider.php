<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Providers;

use Illuminate\Support\ServiceProvider;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Facades\ImageLibrary;

class ImageLibraryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ImageLibrary::registerImageContexts($this->imageContexts());
    }

    /** @return array<ImageContext> */
    public function imageContexts(): array
    {
        // @codeCoverageIgnoreStart
        return [];
        // @codeCoverageIgnoreEnd
    }
}
