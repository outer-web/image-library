<?php

namespace Outerweb\ImageLibrary\Entities;

use Outerweb\ImageLibrary\Facades\ImageLibrary;

class ConversionDefinition
{
    public function __construct(
        public string $name = '',
        public string $labelValue = '',
        public AspectRatio|array|string|null $aspect_ratio = null,
        public ?int $default_width = null,
        public ?int $default_height = null,
        public Effects|array $effects = [],
        public bool $do_translate_label = false,
        public bool $create_sync = false,
    ) {
        if (!is_null($aspect_ratio)) {
            $this->aspectRatio($aspect_ratio);
        }

        $this->effects($effects);

        $this->labelValue = $this->labelValue ?: $this->name;
    }

    public static function make(
        string $name = '',
        string $label = '',
        AspectRatio|array|string|null $aspect_ratio = null,
        ?int $default_width = null,
        ?int $default_height = null,
        Effects|array $effects = [],
        bool $doTranslateLabel = false,
        bool $createSync = false,
    ): self {
        return new self(
            $name,
            $label,
            $aspect_ratio,
            $default_width,
            $default_height,
            $effects,
            $doTranslateLabel,
            $createSync,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? null,
            $data['label'] ?? null,
            $data['aspect_ratio'] ?? null,
            $data['default_width'] ?? null,
            $data['default_height'] ?? null,
            $data['effects'] ?? [],
            $data['do_translate_label'] ?? false,
            $data['create_sync'] ?? false,
        );
    }

    public static function get(string $name): self
    {
        return ImageLibrary::getConversionDefinition($name);
    }

    public function __get(string $key): mixed
    {
        if ($key === 'label') {
            if (blank($this->labelValue)) {
                return $this->name;
            }

            return $this->do_translate_label ? __($this->labelValue) : $this->labelValue;
        }

        return null;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function label(string $label): self
    {
        $this->labelValue = $label;

        return $this;
    }

    public function translateLabel(bool $doTranslateLabel = true): self
    {
        $this->do_translate_label = $doTranslateLabel;

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

    public function createSync(bool $createSync = true): self
    {
        $this->create_sync = $createSync;

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
            'label' => $this->label,
            'aspect_ratio' => (string) $this->aspect_ratio,
            'default_width' => $this->default_width,
            'default_height' => $this->default_height,
            'effects' => $this->effects->toArray(),
        ];
    }
}
