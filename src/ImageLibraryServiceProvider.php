<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary;

use Outerweb\ImageLibrary\Commands\UpgradeCommand;
use Outerweb\ImageLibrary\Components\Image;
use Outerweb\ImageLibrary\Components\Scripts;
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
            ->hasCommands([
                UpgradeCommand::class,
            ])
            ->hasMigrations([
                'create_source_images_table',
                'create_images_table',
            ])
            ->publishesServiceProvider('ImageLibraryServiceProvider')
            ->hasViews()
            ->hasViewComponents(
                'image-library',
                Image::class,
                Scripts::class,
            )
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->copyAndRegisterServiceProviderInApp()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('outer-web/image-library');
            });
    }
}
