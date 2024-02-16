<?php

namespace Outerweb\ImageLibrary\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Outerweb\ImageLibrary\Entities\ConversionDefinition;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\ImageConversion;

class ImageLibrary
{
    private $conversionDefinitions = [];

    public function upload(UploadedFile $file, ?string $disk = null, array $attributes = []): Image
    {
        return $this->imageModel()::upload($file, $disk, $attributes);
    }

    public function imageModel(): string
    {
        return config('image-library.models.image');
    }

    public function imageConversionModel(): string
    {
        return config('image-library.models.image_conversion');
    }

    public function addConversionDefinition(ConversionDefinition|string $definition, array $data = []): self
    {
        if (is_string($definition)) {
            $definition = ConversionDefinition::fromArray([
                'name' => $definition,
                ...$data,
            ]);
        }

        $definition->validate(true);

        $this->conversionDefinitions[] = $definition;

        return $this;
    }

    public function getConversionDefinitions(): Collection
    {
        return collect($this->conversionDefinitions);
    }

    public function getConversionDefinition(string $name): ?ConversionDefinition
    {
        return $this->getConversionDefinitions()->first(fn(ConversionDefinition $definition) => $definition->name === $name);
    }
}
