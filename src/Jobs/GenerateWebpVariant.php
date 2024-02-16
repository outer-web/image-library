<?php

namespace Outerweb\ImageLibrary\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\ImageConversion;

class GenerateWebpVariant implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Image|ImageConversion $image, public bool $force = false)
    {
    }

    public function handle()
    {
        $this->image->generateWebpVariant($this->force);
    }
}
