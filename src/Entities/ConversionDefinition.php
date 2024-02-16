<?php

namespace Outerweb\ImageLibrary\Entities;

class ConversionDefinition
{
    public function __construct(
        public string $name = '',
        public AspectRatio|array|string|null $aspect_ratio = null,
        public ?int $default_width = null,
        public ?int $default_height = null,
        public Effects|array $effects = [],
    ) {
        if (!is_null($aspect_ratio)) {
            $this->aspectRatio($aspect_ratio);
        }

        $this->effects($effects);
    }

    public static function make(
        string $name = '',
        AspectRatio|array|string|null $aspect_ratio = null,
        ?int $default_width = null,
        ?int $default_height = null,
        Effects|array $effects = [],
    ): self {
        return new self(
            $name,
            $aspect_ratio,
            $default_width,
            $default_height,
            $effects,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? null,
            $data['aspect_ratio'] ?? null,
            $data['default_width'] ?? null,
            $data['default_height'] ?? null,
            $data['effects'] ?? [],
        );
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function aspectRatio(AspectRatio|array|string $aspectRatio): self
    {
        if (is_array($aspectRatio)) {
            $aspectRatio = AspectRatio::fromArray($aspectRatio);
        }

        if (is_string($aspectRatio)) {
            $aspectRatio = AspectRatio::fromString($aspectRatio);
        }

        $this->aspect_ratio = $aspectRatio;

        return $this;
    }

    public function defaultWidth(int $defaultWidth): self
    {
        $this->default_width = $defaultWidth;

        return $this;
    }

    public function defaultHeight(int $defaultHeight): self
    {
        $this->default_height = $defaultHeight;

        return $this;
    }

    public function effects(Effects|array $effects): self
    {
        if (is_array($effects)) {
            $effects = Effects::fromArray($effects);
        }

        $this->effects = $effects;

        return $this;
    }

    public function validate(bool $throwExceptions = false): bool
    {
        try {
            if (is_null($this->name)) {
                throw new \InvalidArgumentException('Conversion definition must have a name');
            }

            if (is_null($this->aspect_ratio)) {
                throw new \InvalidArgumentException('Conversion definition must have an aspect ratio');
            }

            $this->aspect_ratio->validate();

            $this->effects->validate();

            return true;
        } catch (\Exception $e) {
            if ($throwExceptions) {
                throw $e;
            }

            return false;
        }
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'aspect_ratio' => (string) $this->aspect_ratio,
            'default_width' => $this->default_width,
            'default_height' => $this->default_height,
            'effects' => $this->effects->toArray(),
        ];
    }
}
