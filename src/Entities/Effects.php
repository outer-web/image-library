<?php

namespace Outerweb\ImageLibrary\Entities;

class Effects
{
    public function __construct(
        public int $blur = 0,
        public int $pixelate = 0,
        public bool $greyscale = false,
        public bool $sepia = false,
        public int $sharpen = 0,
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    public static function fromArray(array $array): self
    {
        return new self(
            $array['blur'] ?? 0,
            $array['pixelate'] ?? 0,
            $array['greyscale'] ?? false,
            $array['sepia'] ?? false,
            $array['sharpen'] ?? 0
        );
    }

    public function blur(int $amount): self
    {
        $this->blur = $amount;

        return $this;
    }

    public function pixelate(int $amount): self
    {
        $this->pixelate = $amount;

        return $this;
    }

    public function greyscale(bool $greyscale = true): self
    {
        $this->greyscale = $greyscale;

        return $this;
    }

    public function sepia(bool $sepia = true): self
    {
        $this->sepia = $sepia;

        return $this;
    }

    public function sharpen(int $amount): self
    {
        $this->sharpen = $amount;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'blur' => $this->blur,
            'pixelate' => $this->pixelate,
            'greyscale' => $this->greyscale,
            'sepia' => $this->sepia,
            'sharpen' => $this->sharpen,
        ];
    }

    public function validate(bool $throwExceptions = false): bool
    {
        try {
            if ($this->blur < 0 || $this->blur > 100) {
                throw new \InvalidArgumentException('Blur amount must be between 0 and 100');
            }

            if ($this->pixelate < 0 || $this->pixelate > 100) {
                throw new \InvalidArgumentException('Pixelate amount must be between 0 and 100');
            }

            if ($this->sharpen < 0 || $this->sharpen > 100) {
                throw new \InvalidArgumentException('Sharpen amount must be between 0 and 100');
            }

            return true;
        } catch (\Exception $e) {
            if ($throwExceptions) {
                throw $e;
            }

            return false;
        }
    }
}
