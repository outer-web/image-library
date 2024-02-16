<?php

namespace Outerweb\ImageLibrary\Facades;

use Illuminate\Support\Facades\Facade;
use Outerweb\ImageLibrary\Services\ImageLibrary as ServicesImageLibrary;

class ImageLibrary extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ServicesImageLibrary::class;
    }
}
