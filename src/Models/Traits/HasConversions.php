<?php

namespace Outerweb\ImageLibrary\Models\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\ImageConversion;

trait HasConversions
{
    public function conversions(): HasMany
    {
        return $this->hasMany(config('image-library.models.image_conversion'));
    }

    public function getConversion(?string $conversionName): ?ImageConversion
    {
        if (is_null($conversionName)) {
            return null;
        }

        return $this->conversions->firstWhere('conversion_name', $conversionName);
    }

    public function createOrUpdateConversions(bool $deleteDeprecated = true): void
    {
        if ($deleteDeprecated) {
            $this->deleteDeprecatedConversions();
        }

        /** @var \Outerweb\ImageLibrary\Entities\ConversionDefinition $definition */
        foreach (ImageLibrary::getConversionDefinitions() as $definition) {
            $existingConversion = $this->getConversion($definition->name);
            $conversionMd5 = md5(json_encode($definition->toArray()));

            if ($existingConversion && $existingConversion->conversion_md5 === $conversionMd5) {
                continue;
            }

            if ($existingConversion) {
                $existingConversion->delete();
            }

            $aspectRatio = $definition->aspect_ratio;
            $defaultWidth = $definition->default_width;
            $defaultHeight = $definition->default_height;

            if (is_null($defaultWidth) && is_null($defaultHeight)) {
                $defaultWidth = $this->width;
                $defaultHeight = $this->height;

                $possibleWidth = round($defaultHeight * $aspectRatio->x / $aspectRatio->y);
                $possibleHeight = round($defaultWidth * $aspectRatio->y / $aspectRatio->x);

                if ($possibleHeight > $defaultHeight) {
                    $defaultWidth = $possibleWidth;
                } elseif ($possibleWidth > $defaultWidth) {
                    $defaultHeight = $possibleHeight;
                }
            }

            if (is_null($defaultWidth)) {
                $defaultWidth = round($defaultHeight * $aspectRatio->x / $aspectRatio->y);
            }

            if (is_null($defaultHeight)) {
                $defaultHeight = round($defaultWidth * $aspectRatio->y / $aspectRatio->x);
            }

            $this->conversions()->create([
                'conversion_name' => $definition->name,
                'conversion_md5' => $conversionMd5,
                'width' => $defaultWidth,
                'height' => $defaultHeight,
                'size' => $this->size,
            ]);
        }
    }

    public function deleteDeprecatedConversions(): void
    {
        $this->conversions()
            ->whereNotIn('conversion_name', ImageLibrary::getConversionDefinitions()->pluck('name'))
            ->each(function (ImageConversion $conversion) {
                $conversion->delete();
            });
    }
}
