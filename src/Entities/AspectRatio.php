<?php

namespace Outerweb\ImageLibrary\Entities;

class AspectRatio
{
    public function __construct(
        public ?int $x,
        public ?int $y,
    ) {
    }

    public static function make(?int $x, ?int $y): self
    {
        return new self($x, $y);
    }

    public static function fromArray(array $array): self
    {
        return new self($array['x'] ?? null, $array['y'] ?? null);
    }

    public static function fromString(string $string): self
    {
        $parts = explode(':', $string);

        return new self((int) $parts[0] ?? null, (int) $parts[1] ?? null);
    }

    public function setX(int $x): self
    {
        $this->x = $x;

        return $this;
    }

    public function setY(int $y): self
    {
        $this->y = $y;

        return $this;
    }

    public function __toString(): string
    {
        return "{$this->x}:{$this->y}";
    }

    public function validate(bool $throwExceptions = false): bool
    {
        try {
            if (is_null($this->x) || is_null($this->y)) {
                throw new \InvalidArgumentException('Aspect ratio must have both X and Y set');
            }

            if ($this->x <= 0) {
                throw new \InvalidArgumentException('Aspect ratio X must be greater than 0');
            }

            if ($this->y <= 0) {
                throw new \InvalidArgumentException('Aspect ratio Y must be greater than 0');
            }

            return true;
        } catch (\InvalidArgumentException $e) {
            if ($throwExceptions) {
                throw $e;
            }

            return false;
        }
    }
}
