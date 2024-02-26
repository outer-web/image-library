<?php

namespace Outerweb\ImageLibrary\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Scripts extends Component
{
    public function render(): View|Closure|string
    {
        return view('image-library::components.scripts');
    }
}
