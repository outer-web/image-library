<?php

namespace Outerweb\ImageLibrary\Console\Commands;

use Illuminate\Console\Command;

class GenerateResponsiveVariants extends Command
{
    protected $signature = 'image-library:generate-responsive-variants {--id=*} {--force}';

    protected $description = 'Generate responsive variants for specific or all images.';

    public function handle()
    {
        $ids = $this->option('id');
        $force = $this->option('force');

        if (count($ids) > 0) {
            $images = $this->getImageClass()::whereIn('id', $ids)
                ->with('conversions')
                ->get();
        } else {
            $images = $this->getImageClass()::query()
                ->with('conversions')
                ->get();
        }

        $progressBar = $this->output->createProgressBar(count($images));
        $progressBar->start();

        $images->each(function ($image) use ($progressBar, $force) {
            $image->GenerateResponsiveVariants($force);
            $image->conversions->each(function ($conversion) use ($force) {
                $conversion->GenerateResponsiveVariants($force);
            });
            $progressBar->advance();
            $this->info(" Generated responsive variants for {$image->id}.");
        });

        $progressBar->finish();

        $this->info(PHP_EOL . 'Responsive variants generated.');
    }

    public function getImageClass(): string
    {
        return config('image-library.models.image');
    }
}
