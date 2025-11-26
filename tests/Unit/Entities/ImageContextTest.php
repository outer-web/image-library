<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Outerweb\ImageLibrary\Entities\AspectRatio;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Enums\Breakpoint;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Spatie\Image\Enums\CropPosition;

it('has a make method', function () {
    $imageContext = ImageContext::make('thumbnail');

    expect($imageContext)
        ->toBeInstanceOf(ImageContext::class);
});

it('can get and get the key', function () {
    $imageContext = new ImageContext('thumbnail');

    expect($imageContext->getKey())
        ->toBe('thumbnail');
});

it('can get a hash string of the configuration', function () {
    $imageContext1 = ImageContext::make('thumbnail')
        ->label('Thumbnail Image')
        ->aspectRatio(AspectRatio::make(16, 9))
        ->blur(5)
        ->grayscale(true)
        ->sepia(false);

    $imageContext2 = ImageContext::make('thumbnail')
        ->label('Thumbnail Image')
        ->aspectRatio(AspectRatio::make(16, 9))
        ->blur(5)
        ->grayscale(true)
        ->sepia(false);

    expect($imageContext1->getConfigurationHash())
        ->toBeString();

    expect($imageContext1->getConfigurationHash())
        ->toEqual($imageContext2->getConfigurationHash());
});

describe('label', function () {
    it('can set and get the label as string', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->label('Thumbnail Image');

        expect($imageContext->getLabel())
            ->toBe('Thumbnail Image');
    });

    it('can set and get the label as closure', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->label(function (ImageContext $imageContext) {
                return Str::title($imageContext->getKey()).' Image from Closure';
            });

        expect($imageContext->getLabel())
            ->toBe('Thumbnail Image from Closure');
    });
});

describe('aspect ratio', function () {
    it('can set and get the aspect ratio for all breakpoints', function () {
        $aspectRatio = AspectRatio::make(16, 9);

        $imageContext = ImageContext::make('thumbnail')
            ->aspectRatio($aspectRatio);

        expect($imageContext->getAspectRatioByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->each->toBe($aspectRatio);
    });

    it('can set and get the aspect ratio per breakpoint', function () {
        $mobileAspectRatio = AspectRatio::make(4, 3);
        $desktopAspectRatio = AspectRatio::make(16, 9);

        $imageContext = ImageContext::make('thumbnail')
            ->aspectRatio([
                Breakpoint::Small->value => $mobileAspectRatio,
                Breakpoint::Medium->value => $mobileAspectRatio,
                Breakpoint::Large->value => $desktopAspectRatio,
                Breakpoint::ExtraLarge->value => $desktopAspectRatio,
                Breakpoint::ExtraExtraLarge->value => $desktopAspectRatio,
            ]);

        expect($imageContext->getAspectRatioByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Small))->toBe($mobileAspectRatio)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Medium))->toBe($mobileAspectRatio)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Large))->toBe($desktopAspectRatio)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraLarge))->toBe($desktopAspectRatio)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe($desktopAspectRatio);
    });

    it('can set and get the aspect ratio for a specific breakpoint', function () {
        $aspectRatio1 = AspectRatio::make(4, 3);
        $aspectRatio2 = AspectRatio::make(16, 9);

        $imageContext = ImageContext::make('thumbnail')
            ->aspectRatio($aspectRatio2)
            ->aspectRatioForBreakpoint(Breakpoint::Small, $aspectRatio1);

        expect($imageContext->getAspectRatioByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Small))->toBe($aspectRatio1)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Medium))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Large))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraLarge))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe($aspectRatio2);
    });

    it('can set and get the aspect ratio for all breakpoints after and including a specific breakpoint', function () {
        $aspectRatio1 = AspectRatio::make(4, 3);
        $aspectRatio2 = AspectRatio::make(16, 9);

        $imageContext = ImageContext::make('thumbnail')
            ->aspectRatio($aspectRatio1)
            ->aspectRatioFromBreakpoint(Breakpoint::Large, $aspectRatio2);

        expect($imageContext->getAspectRatioByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Small))->toBe($aspectRatio1)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Medium))->toBe($aspectRatio1)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Large))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraLarge))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe($aspectRatio2);
    });

    it('can set and get the aspect ratio for all breakpoints before and including a specific breakpoint', function () {
        $aspectRatio1 = AspectRatio::make(4, 3);
        $aspectRatio2 = AspectRatio::make(16, 9);

        $imageContext = ImageContext::make('thumbnail')
            ->aspectRatio($aspectRatio1)
            ->aspectRatioToBreakpoint(Breakpoint::Large, $aspectRatio2);

        expect($imageContext->getAspectRatioByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Small))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Medium))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Large))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraLarge))->toBe($aspectRatio1)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe($aspectRatio1);
    });

    it('can set and get the aspect ratio for all breakpoints between 2 breakpoints', function () {
        $aspectRatio1 = AspectRatio::make(4, 3);
        $aspectRatio2 = AspectRatio::make(16, 9);

        $imageContext = ImageContext::make('thumbnail')
            ->aspectRatio($aspectRatio1)
            ->aspectRatioBetweenBreakpoints(Breakpoint::Medium, Breakpoint::ExtraLarge, $aspectRatio2);

        expect($imageContext->getAspectRatioByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Small))->toBe($aspectRatio1)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Medium))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::Large))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraLarge))->toBe($aspectRatio2)
            ->and($imageContext->getAspectRatioForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe($aspectRatio1);
    });

    it('throws an exception when aspect ratio for a breakpoint is not defined', function () {
        ImageContext::make('thumbnail')
            ->aspectRatio([
                Breakpoint::Small->value => AspectRatio::make(4, 3),
                Breakpoint::Large->value => AspectRatio::make(16, 9),
            ]);
    })->throws(InvalidArgumentException::class, "Aspect ratio for breakpoint 'md' is not defined for ImageContext with key 'thumbnail'.");
});

describe('minWidth', function () {
    it('can set and get the min width for all breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->minWidth(320);

        expect($imageContext->getMinWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->each->toBe(320);
    });

    it('can set and get the min width per breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->minWidth([
                Breakpoint::Small->value => 320,
                Breakpoint::Medium->value => 480,
                Breakpoint::Large->value => 768,
                Breakpoint::ExtraLarge->value => 1024,
                Breakpoint::ExtraExtraLarge->value => 1280,
            ]);

        expect($imageContext->getMinWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Small))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Medium))->toBe(480)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Large))->toBe(768)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(1024)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(1280);
    });

    it('can set and get the min width for a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->minWidth(320)
            ->minWidthForBreakpoint(Breakpoint::Small, 480);

        expect($imageContext->getMinWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Small))->toBe(480)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Medium))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Large))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(320);
    });

    it('can set and get the min width for all breakpoints after and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->minWidth(320)
            ->minWidthFromBreakpoint(Breakpoint::Large, 768);

        expect($imageContext->getMinWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Small))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Medium))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Large))->toBe(768)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(768)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(768);
    });

    it('can set and get the min width for all breakpoints before and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->minWidth(768)
            ->minWidthToBreakpoint(Breakpoint::Large, 320);

        expect($imageContext->getMinWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Small))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Medium))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Large))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(768)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(768);
    });

    it('can set and get the min width for all breakpoints between 2 breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->minWidth(320)
            ->minWidthBetweenBreakpoints(Breakpoint::Medium, Breakpoint::ExtraLarge, 768);

        expect($imageContext->getMinWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Small))->toBe(320)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Medium))->toBe(768)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::Large))->toBe(768)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(768)
            ->and($imageContext->getMinWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(320);
    });

    it('throws an exception when min width for a breakpoint is not defined', function () {
        ImageContext::make('thumbnail')
            ->minWidth([
                Breakpoint::Small->value => 320,
                Breakpoint::Large->value => 768,
            ]);
    })->throws(InvalidArgumentException::class, "Min width for breakpoint 'md' is not defined for ImageContext with key 'thumbnail'.");
});

describe('maxWidth', function () {
    it('can set and get the min width for all breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->maxWidth(320);

        expect($imageContext->getMaxWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->each->toBe(320);
    });

    it('can set and get the min width per breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->maxWidth([
                Breakpoint::Small->value => 320,
                Breakpoint::Medium->value => 480,
                Breakpoint::Large->value => 768,
                Breakpoint::ExtraLarge->value => 1024,
                Breakpoint::ExtraExtraLarge->value => 1280,
            ]);

        expect($imageContext->getMaxWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Small))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Medium))->toBe(480)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Large))->toBe(768)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(1024)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(1280);
    });

    it('can set and get the min width for a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->maxWidth(320)
            ->maxWidthForBreakpoint(Breakpoint::Small, 480);

        expect($imageContext->getMaxWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Small))->toBe(480)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Medium))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Large))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(320);
    });

    it('can set and get the min width for all breakpoints after and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->maxWidth(320)
            ->maxWidthFromBreakpoint(Breakpoint::Large, 768);

        expect($imageContext->getMaxWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Small))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Medium))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Large))->toBe(768)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(768)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(768);
    });

    it('can set and get the min width for all breakpoints before and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->maxWidth(768)
            ->maxWidthToBreakpoint(Breakpoint::Large, 320);

        expect($imageContext->getMaxWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Small))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Medium))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Large))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(768)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(768);
    });

    it('can set and get the min width for all breakpoints between 2 breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->maxWidth(320)
            ->maxWidthBetweenBreakpoints(Breakpoint::Medium, Breakpoint::ExtraLarge, 768);

        expect($imageContext->getMaxWidthByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Small))->toBe(320)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Medium))->toBe(768)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::Large))->toBe(768)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraLarge))->toBe(768)
            ->and($imageContext->getMaxWidthForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(320);
    });

    it('throws an exception when min width for a breakpoint is not defined', function () {
        ImageContext::make('thumbnail')
            ->maxWidth([
                Breakpoint::Small->value => 320,
                Breakpoint::Large->value => 768,
            ]);
    })->throws(InvalidArgumentException::class, "Max width for breakpoint 'md' is not defined for ImageContext with key 'thumbnail'.");
});

describe('cropPosition', function () {
    it('can set and get the crop position for all breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->cropPosition('center');

        expect($imageContext->getCropPositionByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->each->toBe(CropPosition::Center);
    });

    it('can set and get the crop position per breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->cropPosition([
                Breakpoint::Small->value => 'top',
                Breakpoint::Medium->value => 'bottom',
                Breakpoint::Large->value => 'left',
                Breakpoint::ExtraLarge->value => 'right',
                Breakpoint::ExtraExtraLarge->value => 'center',
            ]);

        expect($imageContext->getCropPositionByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Small))->toBe(CropPosition::Top)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Medium))->toBe(CropPosition::Bottom)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Large))->toBe(CropPosition::Left)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraLarge))->toBe(CropPosition::Right)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(CropPosition::Center);
    });

    it('falls back to the default crop position if not set', function () {
        $imageContext = ImageContext::make('thumbnail');

        expect($imageContext->getCropPositionForBreakpoint(Breakpoint::Small))
            ->toBe(ImageLibrary::getDefaultCropPosition());
    });

    it('can use the spatie CropPosition enums', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->cropPosition(CropPosition::Center);

        expect($imageContext->getCropPositionForBreakpoint(Breakpoint::Small))
            ->toBe(CropPosition::Center);
    });

    it('throws an exception when crop position for a breakpoint is not defined', function () {
        ImageContext::make('thumbnail')
            ->cropPosition([
                Breakpoint::Small->value => 'top',
                Breakpoint::Large->value => 'bottom',
            ]);
    })->throws(InvalidArgumentException::class, "Crop position for breakpoint 'md' is not defined for ImageContext with key 'thumbnail'.");

    it('can set and get the crop position for a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->cropPosition('center')
            ->cropPositionForBreakpoint(Breakpoint::Small, 'topLeft');

        expect($imageContext->getCropPositionByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Small))->toBe(CropPosition::TopLeft)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Medium))->toBe(CropPosition::Center)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Large))->toBe(CropPosition::Center)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraLarge))->toBe(CropPosition::Center)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(CropPosition::Center);
    });

    it('can set and get the crop position for all breakpoints after and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->cropPosition('topLeft')
            ->cropPositionFromBreakpoint(Breakpoint::Large, 'bottomRight');

        expect($imageContext->getCropPositionByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Small))->toBe(CropPosition::TopLeft)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Medium))->toBe(CropPosition::TopLeft)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Large))->toBe(CropPosition::BottomRight)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraLarge))->toBe(CropPosition::BottomRight)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(CropPosition::BottomRight);
    });

    it('can set and get the crop position for all breakpoints before and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->cropPosition('topLeft')
            ->cropPositionToBreakpoint(Breakpoint::Large, 'bottomRight');

        expect($imageContext->getCropPositionByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Small))->toBe(CropPosition::BottomRight)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Medium))->toBe(CropPosition::BottomRight)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Large))->toBe(CropPosition::BottomRight)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraLarge))->toBe(CropPosition::TopLeft)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(CropPosition::TopLeft);
    });

    it('can set and get the crop position for all breakpoints between 2 breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->cropPosition('topLeft')
            ->cropPositionBetweenBreakpoints(Breakpoint::Medium, Breakpoint::ExtraLarge, 'bottomRight');

        expect($imageContext->getCropPositionByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Small))->toBe(CropPosition::TopLeft)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Medium))->toBe(CropPosition::BottomRight)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::Large))->toBe(CropPosition::BottomRight)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraLarge))->toBe(CropPosition::BottomRight)
            ->and($imageContext->getCropPositionForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(CropPosition::TopLeft);
    });
});

describe('blur', function () {
    it('can set and get the blur for all breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->blur(5);

        expect($imageContext->getBlurByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->each->toBe(5);
    });

    test('blur must be at least 0', function () {
        ImageContext::make('thumbnail')
            ->blur(-1);
    })->throws(InvalidArgumentException::class, "Blur value must be between 0 and 100 for ImageContext with key 'thumbnail'.");

    test('blur must be at least 0 for each breakpoint', function () {
        ImageContext::make('thumbnail')
            ->blur([
                Breakpoint::Small->value => 5,
                Breakpoint::Medium->value => -2,
            ]);
    })->throws(InvalidArgumentException::class, "Blur value for breakpoint 'md' must be between 0 and 100 for ImageContext with key 'thumbnail'.");

    test('blur must be at most 100', function () {
        ImageContext::make('thumbnail')
            ->blur(101);
    })->throws(InvalidArgumentException::class, "Blur value must be between 0 and 100 for ImageContext with key 'thumbnail'.");

    test('blur must be at most 100 for each breakpoint', function () {
        ImageContext::make('thumbnail')
            ->blur([
                Breakpoint::Small->value => 50,
                Breakpoint::Medium->value => 150,
            ]);
    })->throws(InvalidArgumentException::class, "Blur value for breakpoint 'md' must be between 0 and 100 for ImageContext with key 'thumbnail'.");

    test('blur must be an integer for each breakpoint', function () {
        ImageContext::make('thumbnail')
            ->blur([
                Breakpoint::Small->value => 5,
                Breakpoint::Medium->value => 'high',
            ]);
    })->throws(InvalidArgumentException::class, "Blur value for breakpoint 'md' must be an integer for ImageContext with key 'thumbnail'.");

    it('can set and get the blur per breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->blur([
                Breakpoint::Small->value => 2,
                Breakpoint::Medium->value => 4,
                Breakpoint::Large->value => 6,
                Breakpoint::ExtraLarge->value => 8,
                Breakpoint::ExtraExtraLarge->value => 10,
            ]);

        expect($imageContext->getBlurByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Small))->toBe(2)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Medium))->toBe(4)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Large))->toBe(6)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraLarge))->toBe(8)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(10);
    });

    it('can set and get the blur for a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->blur(0)
            ->blurForBreakpoint(Breakpoint::Small, 5);

        expect($imageContext->getBlurByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Small))->toBe(5)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Medium))->toBe(0)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Large))->toBe(0)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraLarge))->toBe(0)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(0);
    });

    it('can set and get the blur for all breakpoints after and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->blur(0)
            ->blurFromBreakpoint(Breakpoint::Large, 10);

        expect($imageContext->getBlurByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Small))->toBe(0)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Medium))->toBe(0)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Large))->toBe(10)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraLarge))->toBe(10)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(10);
    });

    it('can set and get the blur for all breakpoints before and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->blur(0)
            ->blurToBreakpoint(Breakpoint::Large, 10);

        expect($imageContext->getBlurByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Small))->toBe(10)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Medium))->toBe(10)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Large))->toBe(10)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraLarge))->toBe(0)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(0);
    });

    it('can set and get the blur for all breakpoints between 2 breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->blur(0)
            ->blurBetweenBreakpoints(Breakpoint::Medium, Breakpoint::ExtraLarge, 10);

        expect($imageContext->getBlurByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Small))->toBe(0)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Medium))->toBe(10)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::Large))->toBe(10)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraLarge))->toBe(10)
            ->and($imageContext->getBlurForBreakpoint(Breakpoint::ExtraExtraLarge))->toBe(0);
    });

    it('throws an exception when blur for a breakpoint is not defined', function () {
        ImageContext::make('thumbnail')
            ->blur([
                Breakpoint::Small->value => 5,
                Breakpoint::Large->value => 10,
            ]);
    })->throws(InvalidArgumentException::class, "Blur for breakpoint 'md' is not defined for ImageContext with key 'thumbnail'.");

    it('returns null if not defined', function () {
        $imageContext = ImageContext::make('thumbnail');

        expect($imageContext->getBlurForBreakpoint(Breakpoint::Small))->toBeNull();
    });
});

describe('grayscale', function () {
    it('can set and get the grayscale for all breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->grayscale(true);

        expect($imageContext->getGrayscaleByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->each->toBeTrue();
    });

    it('can set and get the grayscale per breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->grayscale([
                Breakpoint::Small->value => true,
                Breakpoint::Medium->value => false,
                Breakpoint::Large->value => true,
                Breakpoint::ExtraLarge->value => false,
                Breakpoint::ExtraExtraLarge->value => true,
            ]);

        expect($imageContext->getGrayscaleByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Small))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Medium))->toBeFalse()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Large))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraLarge))->toBeFalse()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeTrue();
    });

    it('can set and get the grayscale for a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->grayscale(false)
            ->grayscaleForBreakpoint(Breakpoint::Small, true);

        expect($imageContext->getGrayscaleByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Small))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Medium))->toBeFalse()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Large))->toBeFalse()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraLarge))->toBeFalse()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeFalse();
    });

    it('can set and get the grayscale for all breakpoints after and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->grayscale(false)
            ->grayscaleFromBreakpoint(Breakpoint::Large, true);

        expect($imageContext->getGrayscaleByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Small))->toBeFalse()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Medium))->toBeFalse()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Large))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraLarge))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeTrue();
    });

    it('can set and get the grayscale for all breakpoints before and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->grayscale(false)
            ->grayscaleToBreakpoint(Breakpoint::Large, true);

        expect($imageContext->getGrayscaleByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Small))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Medium))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Large))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraLarge))->toBeFalse()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeFalse();
    });

    it('can set and get the grayscale for all breakpoints between 2 breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->grayscale(false)
            ->grayscaleBetweenBreakpoints(Breakpoint::Medium, Breakpoint::ExtraLarge, true);

        expect($imageContext->getGrayscaleByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Small))->toBeFalse()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Medium))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::Large))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraLarge))->toBeTrue()
            ->and($imageContext->getGrayscaleForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeFalse();
    });

    it('throws an exception when grayscale for a breakpoint is not defined', function () {
        ImageContext::make('thumbnail')
            ->grayscale([
                Breakpoint::Small->value => true,
                Breakpoint::Large->value => false,
            ]);
    })->throws(InvalidArgumentException::class, "Greyscale for breakpoint 'md' is not defined for ImageContext with key 'thumbnail'.");

    it('returns null if not defined', function () {
        $imageContext = ImageContext::make('thumbnail');

        expect($imageContext->getGrayscaleForBreakpoint(Breakpoint::Small))->toBeNull();
    });

    test('grayscale accepts only boolean values for each breakpoint', function () {
        ImageContext::make('thumbnail')
            ->grayscale([
                Breakpoint::Small->value => true,
                Breakpoint::Medium->value => 'yes',
            ]);
    })->throws(InvalidArgumentException::class, "Greyscale value for breakpoint 'md' must be a boolean for ImageContext with key 'thumbnail'.");
});

describe('sepia', function () {
    it('can set and get the sepia for all breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->sepia(true);

        expect($imageContext->getSepiaByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->each->toBeTrue();
    });

    it('can set and get the sepia per breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->sepia([
                Breakpoint::Small->value => true,
                Breakpoint::Medium->value => false,
                Breakpoint::Large->value => true,
                Breakpoint::ExtraLarge->value => false,
                Breakpoint::ExtraExtraLarge->value => true,
            ]);

        expect($imageContext->getSepiaByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Small))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Medium))->toBeFalse()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Large))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraLarge))->toBeFalse()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeTrue();
    });

    it('can set and get the sepia for a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->sepia(false)
            ->sepiaForBreakpoint(Breakpoint::Small, true);

        expect($imageContext->getSepiaByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Small))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Medium))->toBeFalse()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Large))->toBeFalse()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraLarge))->toBeFalse()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeFalse();
    });

    it('can set and get the sepia for all breakpoints after and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->sepia(false)
            ->sepiaFromBreakpoint(Breakpoint::Large, true);

        expect($imageContext->getSepiaByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Small))->toBeFalse()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Medium))->toBeFalse()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Large))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraLarge))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeTrue();
    });

    it('can set and get the sepia for all breakpoints before and including a specific breakpoint', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->sepia(false)
            ->sepiaToBreakpoint(Breakpoint::Large, true);

        expect($imageContext->getSepiaByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Small))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Medium))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Large))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraLarge))->toBeFalse()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeFalse();
    });

    it('can set and get the sepia for all breakpoints between 2 breakpoints', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->sepia(false)
            ->sepiaBetweenBreakpoints(Breakpoint::Medium, Breakpoint::ExtraLarge, true);

        expect($imageContext->getSepiaByBreakpoint())
            ->toHaveCount(count(Breakpoint::cases()))
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Small))->toBeFalse()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Medium))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::Large))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraLarge))->toBeTrue()
            ->and($imageContext->getSepiaForBreakpoint(Breakpoint::ExtraExtraLarge))->toBeFalse();
    });

    it('throws an exception when sepia for a breakpoint is not defined', function () {
        ImageContext::make('thumbnail')
            ->sepia([
                Breakpoint::Small->value => true,
                Breakpoint::Large->value => false,
            ]);
    })->throws(InvalidArgumentException::class, "Sepia for breakpoint 'md' is not defined for ImageContext with key 'thumbnail'.");

    it('returns null if not defined', function () {
        $imageContext = ImageContext::make('thumbnail');

        expect($imageContext->getSepiaForBreakpoint(Breakpoint::Small))->toBeNull();
    });

    test('sepia accepts only boolean values for each breakpoint', function () {
        ImageContext::make('thumbnail')
            ->sepia([
                Breakpoint::Small->value => true,
                Breakpoint::Medium->value => 'yes',
            ]);
    })->throws(InvalidArgumentException::class, "Sepia value for breakpoint 'md' must be a boolean for ImageContext with key 'thumbnail'.");
});

describe('allowsMultiple', function () {
    it('can set and get allowsMultiple', function () {
        $imageContext = ImageContext::make('gallery')
            ->allowsMultiple(true);

        expect($imageContext->getAllowsMultiple())
            ->toBeTrue();
    });
});

describe('generateWebP', function () {
    it('can set and get generateWebP', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->generateWebP(true);

        expect($imageContext->getGenerateWebP())
            ->toBeTrue();
    });

    it('falls back to the config value if not set', function () {
        $imageContext = ImageContext::make('thumbnail');

        Config::set('image-library.generate.webp', true);

        expect($imageContext->getGenerateWebP())
            ->toBeTrue();

        Config::set('image-library.generate.webp', false);

        expect($imageContext->getGenerateWebP())
            ->toBeFalse();
    });
});

describe('generateResponsiveVersions', function () {
    it('can set and get generateResponsiveVersions', function () {
        $imageContext = ImageContext::make('thumbnail')
            ->generateResponsiveVersions(true);

        expect($imageContext->getGenerateResponsiveVersions())
            ->toBeTrue();
    });

    it('falls back to the config value if not set', function () {
        $imageContext = ImageContext::make('thumbnail');

        Config::set('image-library.generate.responsive_versions', true);

        expect($imageContext->getGenerateResponsiveVersions())
            ->toBeTrue();

        Config::set('image-library.generate.responsive_versions', false);

        expect($imageContext->getGenerateResponsiveVersions())
            ->toBeFalse();
    });
});
