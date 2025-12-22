<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Entities;

class CropData
{
    public int $width;

    public int $height;

    public ?int $x = null;

    public ?int $y = null;

    public int $rotate = 0;

    public int $scaleX = 1;

    public int $scaleY = 1;

    public function __construct(int $width, int $height, ?int $x = null, ?int $y = null, int $rotate = 0, int $scaleX = 1, int $scaleY = 1)
    {
        $this->width = $width;
        $this->height = $height;
        $this->x = $x;
        $this->y = $y;
        $this->rotate = $rotate;
        $this->scaleX = $scaleX;
        $this->scaleY = $scaleY;
    }

    public static function make(int $width, int $height, ?int $x = null, ?int $y = null, int $rotate = 0, int $scaleX = 1, int $scaleY = 1): self
    {
        return new self($width, $height, $x, $y, $rotate, $scaleX, $scaleY);
    }

    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'x' => $this->x,
            'y' => $this->y,
            'rotate' => $this->rotate,
            'scaleX' => $this->scaleX,
            'scaleY' => $this->scaleY,
        ];
    }
}
