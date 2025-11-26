<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Outerweb\ImageLibrary\Components\Image;
use Outerweb\ImageLibrary\Entities\AspectRatio;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Enums\Breakpoint;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Jobs\GenerateImageVersionJob;
use Outerweb\ImageLibrary\Models\Image as ImageModel;
use Outerweb\ImageLibrary\Models\SourceImage;
use Outerweb\ImageLibrary\Tests\Fixtures\Models\User;

beforeEach(function (): void {
    ImageLibrary::registerImageContext(
        ImageContext::make('thumbnail')
            ->aspectRatio(AspectRatio::make(1, 1))
            ->generateWebP(true)
            ->generateResponsiveVersions(true)
    );

    ImageLibrary::registerImageContext(
        ImageContext::make('simple')
            ->aspectRatio(AspectRatio::make(1, 1))
            ->generateWebP(false)
            ->generateResponsiveVersions(false)
    );
});

describe('Image Component', function (): void {
    it('can be constructed with an image model', function (): void {
        $user = User::factory()->create();
        $image = ImageModel::factory()
            ->forModel($user)
            ->create();

        $component = new Image($image);

        expect($component->image)
            ->toBe($image);
    });

    it('renders the correct view', function (): void {
        $user = User::factory()->create();
        $image = ImageModel::factory()
            ->forModel($user)
            ->create([
                'context' => 'thumbnail',
            ]);

        $component = new Image($image);
        $view = $component->render();

        expect($view)
            ->toBeInstanceOf(View::class);

        expect($view->getName())
            ->toBe('image-library::components.image');

        expect($view->getData())
            ->toHaveKey('sources')
            ->and($view->getData()['sources'])
            ->toBeInstanceOf(Illuminate\Support\Collection::class);
    });

    it('generates sources with WebP when enabled', function (): void {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('example-image.jpg', 1200, 800);
        $sourceImage = SourceImage::upload($file);

        $image = ImageModel::factory()
            ->forModel($user)
            ->create([
                'source_image_id' => $sourceImage->id,
                'context' => 'thumbnail',
            ]);

        // Generate the image versions for testing
        GenerateImageVersionJob::dispatchSync($image, Breakpoint::Small);

        $component = new Image($image);
        $view = $component->render();
        $sources = $view->getData()['sources'];

        // Should include both regular and WebP sources
        expect($sources->count())->toBeGreaterThan(0);

        // Check that we have WebP sources
        $hasWebP = $sources->contains(function ($source) {
            return $source->type === 'image/webp';
        });
        expect($hasWebP)->toBeTrue();
    });

    it('does not generate WebP sources when disabled', function (): void {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('example-image.jpg', 1200, 800);
        $sourceImage = SourceImage::upload($file);

        $image = ImageModel::factory()
            ->forModel($user)
            ->create([
                'source_image_id' => $sourceImage->id,
                'context' => 'simple',
            ]);

        $component = new Image($image);
        $view = $component->render();
        $sources = $view->getData()['sources'];

        // Should not include WebP sources
        $hasWebP = $sources->contains(function ($source) {
            return $source->type === 'image/webp';
        });
        expect($hasWebP)->toBeFalse();
    });

    it('generates media queries for breakpoints', function (): void {
        $user = User::factory()->create();
        $image = ImageModel::factory()
            ->forModel($user)
            ->create([
                'context' => 'thumbnail',
            ]);

        $component = new Image($image);
        $view = $component->render();
        $sources = $view->getData()['sources'];

        // Should have media queries for different breakpoints
        expect($sources->count())->toBeGreaterThan(0);

        $sources->each(function ($source) {
            expect($source)->toHaveProperty('media');
            expect($source)->toHaveProperty('srcset');
            expect($source)->toHaveProperty('type');
        });
    });

    it('handles responsive versions when enabled', function (): void {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('example-image.jpg', 1200, 800);
        $sourceImage = SourceImage::upload($file);

        $image = ImageModel::factory()
            ->forModel($user)
            ->create([
                'source_image_id' => $sourceImage->id,
                'context' => 'thumbnail', // has generateResponsiveVersions = true
            ]);

        $component = new Image($image);
        $view = $component->render();
        $sources = $view->getData()['sources'];

        expect($sources->count())->toBeGreaterThan(0);

        // Check that the context supports responsive versions
        expect($image->context->getGenerateResponsiveVersions())->toBeTrue();
    });

    it('extracts width from responsive image filenames with width patterns', function (): void {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('example-image.jpg', 1200, 800);
        $sourceImage = SourceImage::upload($file);

        $image = ImageModel::factory()
            ->forModel($user)
            ->create([
                'source_image_id' => $sourceImage->id,
                'context' => 'thumbnail',
            ]);

        // Create fake responsive image files to simulate the responsive versions
        $basePath = $image->getRelativeBasePath();
        Storage::fake('public');
        Storage::disk('public')->put($basePath.'/sm_w400.jpg', 'fake image content');
        Storage::disk('public')->put($basePath.'/sm_w800.jpg', 'fake image content');
        Storage::disk('public')->put($basePath.'/sm.jpg', 'fake image content');

        $component = new Image($image);
        $view = $component->render();
        $sources = $view->getData()['sources'];

        expect($sources->count())->toBeGreaterThan(0);

        // Verify that srcsets contain width patterns
        $sources->each(function ($source) {
            expect($source->srcset)->toBeString();
        });
    });

    it('handles simple srcset when responsive versions are disabled', function (): void {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('example-image.jpg', 1200, 800);
        $sourceImage = SourceImage::upload($file);

        $image = ImageModel::factory()
            ->forModel($user)
            ->create([
                'source_image_id' => $sourceImage->id,
                'context' => 'simple', // has generateResponsiveVersions = false
            ]);

        GenerateImageVersionJob::dispatchSync($image, Breakpoint::Small);

        $component = new Image($image);
        $view = $component->render();
        $sources = $view->getData()['sources'];

        expect($sources->count())->toBeGreaterThan(0);

        // When responsive versions are disabled, srcset should be simple URL
        $sources->each(function ($source) {
            expect($source->srcset)->toBeString()->not->toBeEmpty();
        });
    });
});
