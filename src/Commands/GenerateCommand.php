<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Commands;

use Illuminate\Console\Command;
use Outerweb\ImageLibrary\Facades\ImageLibrary;

class GenerateCommand extends Command
{
    protected $signature = 'image-library:generate {imageIds?* : One or more image IDs to (re)generate}';

    protected $description = 'Generate (or re-generate) image files for one or more given image IDs';

    public function handle(): int
    {
        $imageIds = $this->argument('imageIds');

        $model = ImageLibrary::getImageModel();

        $query = $model::query()
            ->when(! empty($imageIds), fn ($query) => $query->whereIn((new $model)->getKeyName(), $imageIds));

        $count = $query->count();

        if ($count === 0) {
            $this->info('No images found to generate.');

            return Command::SUCCESS;
        }

        $this->info("Generating {$count} image(s)…");
        $this->newLine(1);

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($query->cursor() as $image) {
            $image->generate();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('All images generated successfully.');

        return Command::SUCCESS;
    }
}
