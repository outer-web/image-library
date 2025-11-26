<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Outerweb\ImageLibrary\Entities\AspectRatio;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\SourceImage;
use Outerweb\ImageLibrary\Tests\Fixtures\Models\User;
use Outerweb\ImageLibrary\Tests\TestCase;
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
        $image = SourceImage::factory()
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
        $image = SourceImage::factory()
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

    it('has a name_with_extension attribute', function (): void {
        $image = SourceImage::factory()
            ->create([
                'name' => 'example-image',
                'extension' => 'jpg',
            ]);

        expect($image->name_with_extension)
            ->toEqual('example-image.jpg');
    });
});

describe('methods', function (): void {
    it('can return the relative base path', function (): void {
        $image = SourceImage::factory()
            ->create();

        expect($image->getRelativeBasePath())
            ->toEqual('media-library/'.$image->uuid);
    });

    it('can return the absolute base path', function (): void {
        $image = SourceImage::factory()
            ->create();

        expect($image->getAbsoluteBasePath())
            ->toEqual(Storage::disk($image->disk)->path('media-library/'.$image->uuid));
    });

    it('can return the relative path', function (): void {
        $image = SourceImage::factory()
            ->create([
                'name' => 'example-image',
                'extension' => 'png',
            ]);

        expect($image->getRelativePath())
            ->toEqual('media-library/'.$image->uuid.'/original.png');
    });

    it('can return the absolute path', function (): void {
        $image = SourceImage::factory()
            ->create([
                'name' => 'example-image',
                'extension' => 'png',
            ]);

        expect($image->getAbsolutePath())
            ->toEqual(Storage::disk($image->disk)->path('media-library/'.$image->uuid.'/original.png'));
    });

    describe('upload', function (): void {
        it('can upload a file as a source image', function (): void {
            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            expect($sourceImage)
                ->toBeInstanceOf(SourceImage::class)
                ->and($sourceImage->disk)
                ->toEqual('public')
                ->and($sourceImage->name)
                ->toEqual('example-image')
                ->and($sourceImage->extension)
                ->toEqual('jpg')
                ->and($sourceImage->mime_type)
                ->toEqual('image/jpeg')
                ->and($sourceImage->width)
                ->toEqual(10)
                ->and($sourceImage->height)
                ->toEqual(10);

            Storage::disk($sourceImage->disk)
                ->assertExists($sourceImage->getRelativePath());
        });

        it('cleans up if it fails to upload a file', function (): void {
            /** @var TestCase $this */
            $this->expectException(Throwable::class);

            // Create a mock file that will throw an exception when accessed
            $file = Mockery::mock(UploadedFile::class);
            $file->shouldReceive('getClientOriginalName')->andReturn('corrupt-image.jpg');
            $file->shouldReceive('getClientOriginalExtension')->andReturn('jpg');
            $file->shouldReceive('getClientMimeType')->andReturn('image/jpeg');
            $file->shouldReceive('getRealPath')->andThrow(new RuntimeException('Failed to read file'));

            try {
                SourceImage::upload($file);
            } catch (Throwable $e) {
                expect(Storage::disk('public')->allFiles('media-library'))
                    ->toBeEmpty();

                throw $e;
            }
        });

        it('cleans up the model if it fails to upload a file', function (): void {
            /** @var TestCase $this */
            $this->expectException(Throwable::class);

            $file = UploadedFile::fake()->image('corrupt-image.jpg', 10, 10);

            // Create a mock storage disk that will throw an exception when making a directory
            Storage::shouldReceive('makeDirectory')->andThrow(new RuntimeException('Failed to create directory'));

            try {
                SourceImage::upload($file);
            } catch (Throwable $e) {
                expect(Storage::disk('public')->allFiles('media-library'))
                    ->toBeEmpty();

                throw $e;
            }
        });
    });

    describe('get', function (): void {
        it('can get the content of the source image file', function (): void {
            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $content = $sourceImage->get();

            expect($content)
                ->toBeString()
                ->not->toBeEmpty();
        });
    });

    describe('exists', function (): void {
        it('can check if the source image file exists', function (): void {
            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            expect($sourceImage->exists())
                ->toBeTrue();

            // Delete the file
            Storage::disk($sourceImage->disk)->delete($sourceImage->getRelativePath());

            expect($sourceImage->exists())
                ->toBeFalse();
        });
    });

    describe('missing', function (): void {
        it('can check if the source image file is missing', function (): void {
            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            expect($sourceImage->missing())
                ->toBeFalse();

            // Delete the file
            Storage::disk($sourceImage->disk)->delete($sourceImage->getRelativePath());

            expect($sourceImage->missing())
                ->toBeTrue();
        });
    });

    describe('download', function (): void {
        it('can download the source image file', function (): void {
            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $content = $sourceImage->download();

            expect($content)
                ->toBeInstanceOf(StreamedResponse::class);
        });
    });

    describe('url', function (): void {
        it('can return the URL of the source image file', function (): void {
            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $url = $sourceImage->url();

            expect($url)
                ->toBeString()
                ->not->toBeEmpty();
        });

        it('can return a temporary URL if configured to do so', function (): void {
            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            ImageLibrary::partialMock();

            ImageLibrary::shouldReceive('shouldUseTemporaryUrlsForDisk')
                ->with($sourceImage->disk)
                ->andReturn(true);

            $url = $sourceImage->url();

            expect($url)
                ->toBeString()
                ->not->toBeEmpty();
        });
    });

    describe('temporaryUrl', function (): void {
        it('can return a temporary URL of the source image file', function (): void {
            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            $temporaryUrl = $sourceImage->temporaryUrl(now()->addMinutes(30));

            expect($temporaryUrl)
                ->toBeString()
                ->not->toBeEmpty();
        });

        it('uses the default expiration time if none is provided', function (): void {
            $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

            $sourceImage = SourceImage::upload($file);

            ImageLibrary::partialMock();

            ImageLibrary::shouldReceive('getTemporaryUrlExpirationMinutesForDisk')
                ->with($sourceImage->disk)
                ->andReturn(15);

            $temporaryUrl = $sourceImage->temporaryUrl();

            expect($temporaryUrl)
                ->toBeString()
                ->not->toBeEmpty();
        });
    });
});

describe('observers & events', function (): void {
    it('generates a UUID on saving if not set', function (): void {
        $image = SourceImage::factory()
            ->create([
                'uuid' => null,
            ]);

        expect($image->uuid)
            ->toBeString()
            ->not->toBeEmpty();
    });

    it('deletes all files on deletion', function (): void {
        $file = UploadedFile::fake()->image('example-image.jpg', 10, 10);

        $sourceImage = SourceImage::upload($file);

        expect(Storage::disk($sourceImage->disk)->exists($sourceImage->getRelativeBasePath()))
            ->toBeTrue();

        $sourceImage->delete();

        expect(Storage::disk($sourceImage->disk)->exists($sourceImage->getRelativeBasePath()))
            ->toBeFalse();
    });
});

describe('relations', function (): void {
    it('has many images', function (): void {
        $sourceImage = SourceImage::factory()
            ->create();

        $user = User::factory()
            ->create();

        Image::factory()
            ->count(3)
            ->forModel($user)
            ->create([
                'source_image_id' => $sourceImage->id,
            ]);

        expect($sourceImage->images())
            ->toBeInstanceOf(HasMany::class);

        expect($sourceImage->images)
            ->toHaveCount(3)
            ->each->toBeInstanceOf(Image::class);
    });
});

describe('scopes', function (): void {});
