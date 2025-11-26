<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Tests\Fixtures\Providers;

use Outerweb\ImageLibrary\Entities\AspectRatio;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Enums\Breakpoint;
use Outerweb\ImageLibrary\Providers\ImageLibraryServiceProvider as BaseImageLibraryServiceProvider;
use Override;

class ImageLibraryServiceProvider extends BaseImageLibraryServiceProvider
{
    #[Override]
    public function imageContexts(): array
    {
        return [
            ImageContext::make('context-single')
                ->aspectRatio(
                    AspectRatio::make(1, 1)
                )
                ->maxWidth([
                    Breakpoint::Small->value => 300,
                    Breakpoint::Medium->value => 600,
                    Breakpoint::Large->value => 900,
                    Breakpoint::ExtraLarge->value => 1200,
                    Breakpoint::ExtraExtraLarge->value => 1500,
                ])
                ->allowsMultiple(false),
            ImageContext::make('context-multiple')
                ->aspectRatio(
                    AspectRatio::make(1, 1)
                )
                ->maxWidth([
                    Breakpoint::Small->value => 300,
                    Breakpoint::Medium->value => 600,
                    Breakpoint::Large->value => 900,
                    Breakpoint::ExtraLarge->value => 1200,
                    Breakpoint::ExtraExtraLarge->value => 1500,
                ])
                ->allowsMultiple(true),
        ];
    }
}
