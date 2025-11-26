<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Components;

use Closure;
use Illuminate\View\Component;
use Illuminate\View\View;

class Scripts extends Component
{
    public function __construct() {}

    public function render(): View|Closure|string
    {
        return view('image-library::components.scripts');
    }
}
