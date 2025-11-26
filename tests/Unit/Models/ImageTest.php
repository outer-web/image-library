<?php

declare(strict_types=1);

use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Outerweb\ImageLibrary\Entities\AspectRatio;
use Outerweb\ImageLibrary\Entities\CropData;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Enums\Breakpoint;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Jobs\GenerateImageVersionJob;
use Outerweb\ImageLibrary\Jobs\GenerateResponsiveImageVersionsJob;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\SourceImage;
use Outerweb\ImageLibrary\Tests\Fixtures\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function (): void {
    ImageLibrary::registerImageContext(
        ImageContext::make('thumbnail')
            ->aspectRatio(
                AspectRatio::make(1, 1)
            ),
    );
});

describe('mutators and casts', function (): void {
    it('has a translatable alt_text attribute', function (): void {
        $user = User::factory()
            ->create();

        $image = Image::factory()
            ->forModel($user)
            ->create([
                'alt_text' => [
                    'en' => 'An example image',
                    'nl' => 'Een voorbeeldafbeelding',
                ],
            ]);

        expect($image->getTranslations('alt_text'))
            ->toEqual([
                'en' => 'An example image',
                'nl' => 'Een voorbeeldafbeelding',
            ]);

        expect($image->alt_text)
            ->toEqual('An example image');

        app()->setLocale('nl');

        expect($image->alt_text)
            ->toEqual('Een voorbeeldafbeelding');
    });

    it('casts the custom_properties attribute to an array', function (): void {
        $user = User::factory()
            ->create();

        $image = Image::factory()
            ->forModel($user)
            ->create([
                'custom_properties' => [
                    'photographer' => 'John Doe',
                    'location' => 'New York',
                ],
            ]);

        expect($image->custom_properties)
            ->toEqual([
                'photographer' => 'John Doe',
                'location' => 'New York',
            ]);
    });

    it('always generates crop data for each breakpoint based on the inputted value', function (): void {
        $user = User::factory()
            ->create();

        $image = Image::factory()
            ->forModel($user)
            ->create([
                'crop_data' => CropData::make(10, 10, 100, 100),
            ])
            ->refresh();

        expect($image->crop_data)
            ->toHaveCount(count(Breakpoint::cases()));

        foreach (Breakpoint::cases() as $breakpoint) {
            expect($image->crop_data[$breakpoint->value])
                ->toBeInstanceOf(CropData::class);
        }
    });

    test('crop_data returns null for each breakpoint if null is provided', function (): void {
        $user = User::factory()
            ->create();

        $sourceImage = SourceImage::factory()
            ->create();

        DB::table('images')
            ->insert([
                'id' => 1,
                'source_image_id' => $sourceImage->id,
                'model_type' => User::class,
                'model_id' => $user->id,
                'disk' => 'public',
                'uuid' => Str::uuid(),
                'context' => 'thumbnail',
                'context_configuration_hash' => ImageLibrary::getImageContextByKey('thumbnail')
                    ?->getConfigurationHash(),
                'crop_data' => null,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $image = Image::query()
            ->first();

        expect($image->crop_data)
            ->toHaveCount(count(Breakpoint::cases()));

        foreach (Breakpoint::cases() as $breakpoint) {
            expect($image->crop_data[$breakpoint->value])
                ->toBeNull();
        }
    });

    test('crop_data returns null on wrongly structured data (not an array)', function (): void {
        $user = User::factory()
            ->create();

        $sourceImage = SourceImage::factory()
            ->create();

        DB::table('images')
            ->insert([
                'id' => 1,
                'source_image_id' => $sourceImage->id,
                'model_type' => User::class,
                'model_id' => $user->id,
                'disk' => 'public',
                'uuid' => Str::uuid(),
                'context' => 'thumbnail',
                'context_configuration_hash' => ImageLibrary::getImageContextByKey('thumbnail')
                    ?->getConfigurationHash(),
                'crop_data' => '"invalid structure"',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $image = Image::query()
            ->first();

        expect($image->crop_data)
            ->toHaveCount(count(Breakpoint::cases()));

        foreach (Breakpoint::cases() as $breakpoint) {
            expect($image->crop_data[$breakpoint->value])
                ->toBeNull();
        }
    });

    test('crop_data returns null on wrongly structured data (missing keys)', function (): void {
        $user = User::factory()
            ->create();

        $sourceImage = SourceImage::factory()
            ->create();

        DB::table('images')
            ->insert([
                'id' => 1,
                'source_image_id' => $sourceImage->id,
                'model_type' => User::class,
                'model_id' => $user->id,
                'disk' => 'public',
                'uuid' => Str::uuid(),
                'context' => 'thumbnail',
                'context_configuration_hash' => ImageLibrary::getImageContextByKey('thumbnail')
                    ?->getConfigurationHash(),
                'crop_data' => json_encode([
                    'small' => [
                        'width' => 100,
                        'height' => 100,
                        // Missing x and y
                    ],
                ]),
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $image = Image::query()
            ->first();

        expect($image->crop_data)
            ->toHaveCount(count(Breakpoint::cases()));

        foreach (Breakpoint::cases() as $breakpoint) {
            expect($image->crop_data[$breakpoint->value])
                ->toBeNull();
        }
    });
});

describe('methods', function (): void {
    describe('getRelativeBasePath', function (): void {
        it('returns the correct relative base path', function (): void {
            $user = User::factory()
                ->create();

            $image = Image::factory()
                ->forModel($user)
                ->create();

            expect($image->getRelativeBasePath())
                ->toBe("image-library/{$image->sourceImage->uuid}/{$image->uuid}");
        });
    });

    describe('getAbsoluteBasePath', function (): void {
        it('returns the correct absolute base path', function (): void {
            $user = User::factory()
                ->create();

            $image = Image::factory()
                ->forModel($user)
                ->create();

            expect($image->getAbsoluteBasePath())
                ->toBe(Storage::disk($image->disk)->path("image-library/{$image->sourceImage->uuid}/{$image->uuid}"));
        });
    });

    describe('getRelativePathForBreakpoint', function (): void {
        it('returns the correct relative path for the given breakpoint', function (): void {
            $user = User::factory()
                ->create();

            $image = Image::factory()
                ->forModel($user)
                ->create();

            expect($image->getRelativePathForBreakpoint(Breakpoint::Small))
                ->toBe("image-library/{$image->sourceImage->uuid}/{$image->uuid}/sm.{$image->sourceImage->extension}");

            expect($image->getRelativePathForBreakpoint(Breakpoint::Medium, 'png'))
                ->toBe("image-library/{$image->sourceImage->uuid}/{$image->uuid}/md.png");
        });
    });

    describe('getAbsolutePathForBreakpoint', function (): void {
        it('returns the correct absolute path for the given breakpoint', function (): void {
            $user = User::factory()
                ->create();

            $image = Image::factory()
                ->forModel($user)
                ->create();

            expect($image->getAbsolutePathForBreakpoint(Breakpoint::Small))
                ->toBe(Storage::disk($image->disk)->path("image-library/{$image->sourceImage->uuid}/{$image->uuid}/sm.{$image->sourceImage->extension}"));

            expect($image->getAbsolutePathForBreakpoint(Breakpoint::Medium, 'png'))
                ->toBe(Storage::disk($image->disk)->path("image-library/{$image->sourceImage->uuid}/{$image->uuid}/md.png"));
        });
    });

    describe('getForBreakpoint', function (): void {
        it('returns the image content for the given breakpoint', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            $job = new GenerateImageVersionJob($image, Breakpoint::Small);
            $job->handle();

            $breakpointImage = $image->getForBreakpoint(Breakpoint::Small);

            expect($breakpointImage)
                ->toBeString()
                ->not->toBeEmpty();
        });
    });

    describe('existsForBreakpoint', function (): void {
        it('returns whether an image exists for the given breakpoint', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            expect($image->existsForBreakpoint(Breakpoint::Small))
                ->toBeFalse();

            $job = new GenerateImageVersionJob($image, Breakpoint::Small);
            $job->handle();

            expect($image->existsForBreakpoint(Breakpoint::Small))
                ->toBeTrue();
        });
    });

    describe('missingForBreakpoint', function (): void {
        it('returns whether an image is missing for the given breakpoint', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            expect($image->missingForBreakpoint(Breakpoint::Small))
                ->toBeTrue();

            $job = new GenerateImageVersionJob($image, Breakpoint::Small);
            $job->handle();

            expect($image->missingForBreakpoint(Breakpoint::Small))
                ->toBeFalse();
        });
    });

    describe('downloadForBreakpoint', function (): void {
        it('returns a download response for the given breakpoint', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            $job = new GenerateImageVersionJob($image, Breakpoint::Small);
            $job->handle();

            $response = $image->downloadForBreakpoint(Breakpoint::Small);

            expect($response)
                ->toBeInstanceOf(StreamedResponse::class);
        });
    });

    describe('urlForBreakpoint', function (): void {
        it('returns a URL for the given breakpoint', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            GenerateImageVersionJob::dispatchSync($image, Breakpoint::Small);

            $url = $image->urlForBreakpoint(Breakpoint::Small);

            expect($url)
                ->toBeString()
                ->not->toBeEmpty();
        });

        it('can return a temporary URL if configured to do so', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            GenerateImageVersionJob::dispatchSync($image, Breakpoint::Small);

            ImageLibrary::partialMock();

            ImageLibrary::shouldReceive('shouldUseTemporaryUrlsForDisk')
                ->with($image->disk)
                ->andReturn(true);

            $url = $image->urlForBreakpoint(Breakpoint::Small);

            expect($url)
                ->toBeString()
                ->not->toBeEmpty();
        });
    });

    describe('urlForRelativePath', function (): void {
        it('returns a URL for the given relative path', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            $relativePath = "image-library/{$image->sourceImage->uuid}/{$image->uuid}/test-file.jpg";

            $url = $image->urlForRelativePath($relativePath);

            expect($url)
                ->toBeString()
                ->not->toBeEmpty()
                ->toContain($relativePath);
        });

        it('returns a temporary URL if configured to use temporary URLs for the disk', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            ImageLibrary::partialMock();

            ImageLibrary::shouldReceive('shouldUseTemporaryUrlsForDisk')
                ->with($image->disk)
                ->andReturn(true);

            ImageLibrary::shouldReceive('getTemporaryUrlExpirationMinutesForDisk')
                ->with($image->disk)
                ->andReturn(60);

            $relativePath = "image-library/{$image->sourceImage->uuid}/{$image->uuid}/test-file.jpg";

            $url = $image->urlForRelativePath($relativePath);

            expect($url)
                ->toBeString()
                ->not->toBeEmpty();
        });
    });

    describe('temporaryUrlForRelativePath', function (): void {
        it('returns a temporary URL for the given relative path', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            ImageLibrary::partialMock();

            ImageLibrary::shouldReceive('getTemporaryUrlExpirationMinutesForDisk')
                ->with($image->disk)
                ->andReturn(60);

            $relativePath = "image-library/{$image->sourceImage->uuid}/{$image->uuid}/test-file.jpg";

            $url = $image->temporaryUrlForRelativePath($relativePath);

            expect($url)
                ->toBeString()
                ->not->toBeEmpty();
        });

        it('accepts custom expiration time', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            $relativePath = "image-library/{$image->sourceImage->uuid}/{$image->uuid}/test-file.jpg";
            $customExpiration = now()->addHours(2);

            $url = $image->temporaryUrlForRelativePath($relativePath, $customExpiration);

            expect($url)
                ->toBeString()
                ->not->toBeEmpty();
        });

        it('accepts custom options for temporary URL generation', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            ImageLibrary::partialMock();

            ImageLibrary::shouldReceive('getTemporaryUrlExpirationMinutesForDisk')
                ->with($image->disk)
                ->andReturn(60);

            $relativePath = "image-library/{$image->sourceImage->uuid}/{$image->uuid}/test-file.jpg";
            $options = ['ResponseContentType' => 'image/jpeg'];

            $url = $image->temporaryUrlForRelativePath($relativePath, null, $options);

            expect($url)
                ->toBeString()
                ->not->toBeEmpty();
        });
    });

    describe('getResponsiveRelativePathsForBreakpoint', function (): void {
        it('returns multiple responsive relative paths for the given breakpoint', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 1200, 800);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            new GenerateImageVersionJob($image->id, Breakpoint::Large)->handle();

            new GenerateResponsiveImageVersionsJob($image->id, Breakpoint::Large)
                ->handle();

            expect($image->getResponsiveRelativePathsForBreakpoint(Breakpoint::Large))
                ->toBeInstanceOf(Collection::class);

            expect($image->getResponsiveRelativePathsForBreakpoint(Breakpoint::Large)->count())
                ->toBeGreaterThan(0);
        });
    });

    describe('getResponsiveAbsolutePathsForBreakpoint', function (): void {
        it('returns multiple responsive absolute paths for the given breakpoint', function (): void {
            $user = User::factory()
                ->create();

            $file = UploadedFile::fake()->image('example-image.jpg', 1200, 800);

            $sourceImage = SourceImage::upload($file);

            $image = Image::factory()
                ->forModel($user)
                ->create([
                    'source_image_id' => $sourceImage->id,
                ]);

            new GenerateImageVersionJob($image->id, Breakpoint::Large)->handle();

            new GenerateResponsiveImageVersionsJob($image->id, Breakpoint::Large)
                ->handle();

            expect($image->getResponsiveAbsolutePathsForBreakpoint(Breakpoint::Large))
                ->toBeInstanceOf(Collection::class);

            expect($image->getResponsiveAbsolutePathsForBreakpoint(Breakpoint::Large)->count())
                ->toBeGreaterThan(0);
        });
    });
});

describe('observers & events', function (): void {
    it('generates a UUID on saving if not set', function (): void {
        $user = User::factory()
            ->create();

        $image = Image::factory()
            ->forModel($user)
            ->create([
                'uuid' => null,
            ]);

        expect($image->uuid)
            ->toBeString()
            ->not->toBeEmpty();
    });

    it('generates the context_configuration_hash on saving, even if set', function (): void {
        $user = User::factory()
            ->create();

        $image = Image::factory()
            ->forModel($user)
            ->create([
                'context' => 'thumbnail',
                'context_configuration_hash' => null,
            ]);

        $expectedHash = ImageLibrary::getImageContextByKey('thumbnail')
            ?->getConfigurationHash();

        expect($image->context_configuration_hash)
            ->toBe($expectedHash);

        ImageLibrary::registerImageContext(
            ImageContext::make('thumbnail2')
                ->aspectRatio(
                    AspectRatio::make(4, 3)
                ),
        );

        $image->context = 'thumbnail2';
        $image->save();

        $expectedHash = ImageLibrary::getImageContextByKey('thumbnail2')
            ?->getConfigurationHash();

        expect($image->context_configuration_hash)
            ->toBe($expectedHash);
    });

    it('dispatches multiple jobs via a bus chain after creating an image', function (): void {
        Bus::fake();

        $user = User::factory()
            ->create();

        Image::factory()
            ->forModel($user)
            ->create();

        Bus::assertChained([
            Bus::chainedBatch(function (PendingBatch $batch) {
                return $batch->jobs->count() === count(Breakpoint::cases())
                    && $batch->jobs->every(fn ($job) => $job instanceof GenerateImageVersionJob);
            }),
            Bus::chainedBatch(function (PendingBatch $batch) {
                return $batch->jobs->count() === count(Breakpoint::cases())
                    && $batch->jobs->every(fn ($job) => $job instanceof GenerateResponsiveImageVersionsJob);
            }),
        ]);
    });
});

describe('relations', function (): void {
    it('morphs to a model', function (): void {
        $user = User::factory()
            ->create();

        $image = Image::factory()
            ->forModel($user)
            ->create();

        expect($image->model())
            ->toBeInstanceOf(MorphTo::class);

        expect($image->model)
            ->toBeInstanceOf(User::class);
    });

    it('belongs to a source image', function (): void {
        $user = User::factory()
            ->create();

        $image = Image::factory()
            ->forModel($user)
            ->create();

        expect($image->sourceImage())
            ->toBeInstanceOf(BelongsTo::class);

        expect($image->sourceImage)
            ->toBeInstanceOf(SourceImage::class);
    });
});

describe('scopes', function (): void {});
