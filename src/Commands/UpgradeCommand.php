<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class UpgradeCommand extends Command
{
    protected $signature = 'image-library:upgrade';

    protected $description = 'Upgrade the Image Library package to the latest version';

    public function handle(): int
    {
        $this->info('The upgrade will go through the following steps:');
        $this->line('1. Check if an upgrade is needed.');
        $this->line('2. Create the source_images table migration if it does not exist.');
        $this->line('3. Create the new images table migration if it does not exist.');
        $this->line('4. Create pre upgrade migration file if it does not exist. This will rename the existing images table to tmp_images');
        $this->line('5. Create post upgrade migration file if it does not exist. This will create a source_image for each old image and upload the file to the correct location.');
        $this->line('6. Ask to run the migrations if any were created.');
        $this->line('7. Inform you to migrate your custom tables and data as needed.');
        $this->line('8. Ask to create a cleanup migration to remove old image library data.');
        $this->line('9. Ask to run the cleanup migration.');

        if (! $this->confirm('Do you wish to proceed with the upgrade?', true)) {
            $this->warn('Upgrade cancelled.');

            return Command::SUCCESS;
        }

        $this->info('1. Checking if an upgrade is needed...');

        $imagesTableMigrations = collect(File::files(database_path('migrations')))
            ->filter(function ($file) {
                return str_ends_with($file->getFilename(), 'create_images_table.php');
            })
            ->sortBy(fn ($file) => $file->getCTime());

        if ($imagesTableMigrations->isEmpty()) {
            $this->warn('No existing images table migration found. No upgrade needed.');

            return Command::SUCCESS;
        }

        $this->line('Upgrade needed. Proceeding to the next steps...');

        $hasAddedMigrations = false;

        $this->info('2. Creating source_images table migration if it does not exist...');

        $sourceImagesTableMigrations = collect(File::files(database_path('migrations')))
            ->filter(function ($file) {
                return str_ends_with($file->getFilename(), 'create_source_images_table.php');
            })
            ->sortBy(fn ($file) => $file->getCTime());

        if ($sourceImagesTableMigrations->isEmpty()) {
            File::copy(
                __DIR__.'/../../database/migrations/create_source_images_table.php.stub',
                database_path('migrations/'.Carbon::now()->format('Y_m_d_His').'_create_source_images_table.php')
            );

            $this->line('Created source_images table migration.');

            $hasAddedMigrations = true;
        } else {
            $this->line('source_images table migration already exists. Skipping...');
        }

        $this->info('3. Creating new images table migration if it does not exist...');

        if ($imagesTableMigrations->count() === 1) {
            File::copy(
                __DIR__.'/../../database/migrations/create_images_table.php.stub',
                database_path('migrations/'.Carbon::now()->addSecond()->format('Y_m_d_His').'_create_images_table.php')
            );

            $hasAddedMigrations = true;

            $imagesTableMigrations = collect(File::files(database_path('migrations')))
                ->filter(function ($file) {
                    return str_ends_with($file->getFilename(), 'create_images_table.php');
                })
                ->sortBy(fn ($file) => $file->getCTime());

            $this->line('Created new images table migration.');
        } else {
            $this->line('New images table migration already exists. Skipping...');
        }

        $preUpgradeMigrationExists = collect(File::files(database_path('migrations')))
            ->contains(function ($file) {
                return str_ends_with($file->getFilename(), 'pre_image_library_upgrade.php');
            });

        $postUpgradeMigrationExists = collect(File::files(database_path('migrations')))
            ->contains(function ($file) {
                return str_ends_with($file->getFilename(), 'post_image_library_upgrade.php');
            });

        if (! $preUpgradeMigrationExists || ! $postUpgradeMigrationExists) {
            $createImagesTableMigrationTimestamp = pathinfo($imagesTableMigrations->last()->getFilename(), PATHINFO_FILENAME);
            $createImagesTableMigrationTimestamp = mb_substr($createImagesTableMigrationTimestamp, 0, 17);
            $createImagesTableMigrationTimestamp = Carbon::createFromFormat('Y_m_d_His', $createImagesTableMigrationTimestamp);

            $preUpgradeMigrationPath = __DIR__.'/../../database/migrations/upgrade/pre_image_library_upgrade.php.stub';
            $postUpgradeMigrationPath = __DIR__.'/../../database/migrations/upgrade/post_image_library_upgrade.php.stub';

            $this->info('4. Creating pre upgrade migration if it does not exist...');

            if (! $preUpgradeMigrationExists) {
                File::copy(
                    $preUpgradeMigrationPath,
                    database_path('migrations/'.$createImagesTableMigrationTimestamp->subMinute()->format('Y_m_d_His').'_pre_image_library_upgrade.php')
                );

                $hasAddedMigrations = true;

                $this->line('Created pre upgrade migration.');
            } else {
                $this->line('Pre upgrade migration already exists. Skipping...');
            }

            $this->info('5. Creating post upgrade migration if it does not exist...');

            if (! $postUpgradeMigrationExists) {
                File::copy(
                    $postUpgradeMigrationPath,
                    database_path('migrations/'.$createImagesTableMigrationTimestamp->addMinute()->format('Y_m_d_His').'_post_image_library_upgrade.php')
                );

                $hasAddedMigrations = true;

                $this->line('Created post upgrade migration.');
            } else {
                $this->line('Post upgrade migration already exists. Skipping...');
            }
        } else {
            $this->info('4. Creating pre upgrade migration if it does not exist...');
            $this->line('Pre upgrade migration already exists. Skipping...');
            $this->info('5. Creating post upgrade migration if it does not exist...');
            $this->line('Post upgrade migration already exists. Skipping...');
        }

        $this->info('6. Checking if any migrations were created...');

        if ($hasAddedMigrations && $this->confirm('Would you like to run the migrations now?')) {
            $this->line('Running migrations...');

            $this->call('migrate');
        } else {
            $this->line('No new migrations were created or you chose not to run them now.');
        }

        $this->info('7. Now is the time to migrate your custom tables and data as needed.');
        $this->line('You should follow the instructions in the README.md to set up your models and Image Contexts.');
        $this->line('');
        $this->line('You can use the tmp_images table as a reference for your migrations.');
        $this->line('The uuid column of the records in the tmp_images table corresponds to the uuid column in the new source_images table.');
        $this->line('You can use the following id mapping query:');
        $this->line('');
        $this->line('$mapping = DB::table(\'tmp_images\')');
        $this->line('   ->join(\'source_images\', \'tmp_images.uuid\', \'=\', \'source_images.uuid\')');
        $this->line('   ->pluck("tmp_images.id as old_id", "source_images.id as new_id");');
        $this->line('');
        $this->line('Now, for each instance of your models, run the following:');
        $this->line('');
        $this->line('foreach (YourModel::cursor() as $model) {');
        $this->line('    $model->attachImage(');
        $this->line('        SourceImage::find($mapping[$model->{your_old_id_reference}]), // Replace {your_old_id_reference} with the old image ID reference column');
        $this->line('        [');
        $this->line('            \'context\' => \'{your-context-key}\', // Replace {your-context-key} with the appropriate context key');
        $this->line('            // Add any other attributes you need here');
        $this->line('        ]');
        $this->line('    );');
        $this->line('}');
        $this->line('');
        $this->line('Make sure to replace {your_old_id_reference} and {your-context-key} with the appropriate values for your application.');

        $done = false;

        while (! $done) {
            $done = $this->confirm('Have you completed your custom data migrations?');
        }

        if ($this->confirm('Would you like to clean up old image library data now?')) {
            $this->line('Cleaning up old image library data...');

            File::copy(
                __DIR__.'/../../database/migrations/upgrade/cleanup_image_library_upgrade.php.stub',
                database_path('migrations/'.Carbon::now()->format('Y_m_d_His').'_cleanup_image_library_upgrade.php')
            );

            $this->comment('Published the cleanup migration file.');

            if ($this->confirm('Would you like to run the migrations now?')) {
                $this->comment('Running migrations...');

                $this->call('migrate');

                $this->info('Old image library data cleaned up successfully.');
            }
        }

        return Command::SUCCESS;
    }
}
