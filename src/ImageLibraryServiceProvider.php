<?php

namespace Outerweb\ImageLibrary;

use \Outerweb\ImageLibrary\Components;
use Outerweb\ImageLibrary\Console\Commands;
use Outerweb\ImageLibrary\Services\ImageLibrary;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ImageLibraryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('image-library')
            ->hasConfigFile()
            ->hasMigrations([
                'create_images_table',
                'create_image_conversions_table',
            ])
            ->hasCommands([
                Commands\CreateOrUpdateConversions::class,
                Commands\GenerateConversions::class,
                Commands\GenerateResponsiveVariants::class,
            ])
            ->hasViews()
            ->hasViewComponents(
                config('image-library.blade_component_prefix', ''),
                Components\Image::class,
                Components\Picture::class,
            )
            ->hasInstallCommand(function (InstallCommand $command) {
                $composerFile = file_get_contents(__DIR__ . '/../composer.json');

                if ($composerFile) {
                    $githubRepo = json_decode($composerFile, true)['homepage'] ?? null;

                    if ($githubRepo) {
                        $command
                            ->askToStarRepoOnGitHub($githubRepo);
                    }
                }
            });
    }

    public function register()
    {
        parent::register();

        $this->app->singleton(ImageLibrary::class, function () {
            return new ImageLibrary();
        });
    }
}
