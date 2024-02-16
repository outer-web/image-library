<?php

namespace Outerweb\ImageLibrary\Console\Commands;

use Illuminate\Console\Command;

class GenerateConversions extends Command
{
    protected $signature = 'image-library:generate-conversions {--id=*} {--force}';

    protected $description = 'Generate image conversions for specific or all images.';

    public function handle()
    {
        $ids = $this->option('id');
        $force = $this->option('force');

        if (count($ids) > 0) {
            $images = $this->getImageClass()::whereIn('id', $ids)->get();
        } else {
            $images = $this->getImageClass()::get();
        }

        $progressBar = $this->output->createProgressBar(count($images));
        $progressBar->start();

        $images->each(function ($image) use ($progressBar, $force) {
            $image->conversions->each(function ($conversion) use ($force) {
                $conversion->generate($force);
            });
            $progressBar->advance();
            $this->info(" Generated conversions for {$image->id}.");
        });

        $progressBar->finish();

        $this->info(PHP_EOL . 'Conversions generated.');
    }

    public function getImageClass(): string
    {
        return config('image-library.models.image');
    }
}
