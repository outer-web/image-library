<?php

namespace Outerweb\ImageLibrary\Console\Commands;

use Illuminate\Console\Command;

class CreateOrUpdateConversions extends Command
{
    protected $signature = 'image-library:create-or-update-conversions {--id=*} {--delete-deprecated}';

    protected $description = 'Create or update image conversions for specific or all images.';

    public function handle()
    {
        $ids = $this->option('id');
        $deleteDeprecated = $this->option('delete-deprecated');

        if (count($ids) > 0) {
            $images = $this->getImageClass()::whereIn('id', $ids)->get();
        } else {
            $images = $this->getImageClass()::get();
        }

        $progressBar = $this->output->createProgressBar(count($images));
        $progressBar->start();

        $images->each(function ($image) use ($progressBar, $deleteDeprecated) {
            $image->createOrUpdateConversions($deleteDeprecated);
            $progressBar->advance();
            $this->info(" Created conversions for {$image->id}.");
        });

        $progressBar->finish();

        $this->info(PHP_EOL . 'Conversions created.');
    }

    public function getImageClass(): string
    {
        return config('image-library.models.image');
    }
}
