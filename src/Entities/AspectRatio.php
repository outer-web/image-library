<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Entities;

class AspectRatio
{
    public int $horizontal;

    public int $vertical;

    public function __construct(int $horizontal, int $vertical)
    {
        $this->horizontal = $horizontal;
        $this->vertical = $vertical;
    }

    public function __toString(): string
    {
        return "{$this->horizontal}:{$this->vertical}";
    }

    public static function make(int $horizontal, int $vertical): self
    {
        return new self($horizontal, $vertical);
    }

    public function toString(): string
    {
        return (string) $this;
    }

    public function toArray(): array
    {
        return [
            'horizontal' => $this->horizontal,
            'vertical' => $this->vertical,
        ];
    }
}
