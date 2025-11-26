<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Entities;

class CropData
{
    public int $width;

    public int $height;

    public ?int $x = null;

    public ?int $y = null;

    public function __construct(int $width, int $height, ?int $x = null, ?int $y = null)
    {
        $this->width = $width;
        $this->height = $height;
        $this->x = $x;
        $this->y = $y;
    }

    public static function make(int $width, int $height, ?int $x = null, ?int $y = null): self
    {
        return new self($width, $height, $x, $y);
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}
