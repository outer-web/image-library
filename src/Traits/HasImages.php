<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Outerweb\ImageLibrary\Entities\ImageContext;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\Image;
use Outerweb\ImageLibrary\Models\SourceImage;
use Throwable;

trait HasImages
{
    public function images(): MorphMany
    {
        return $this->morphMany(ImageLibrary::getImageModel(), 'model');
    }

    public function attachImage(SourceImage $image, array $attributes = [], string $relation = 'images'): Image
    {
        if (! array_key_exists('context', $attributes)) {
            throw new InvalidArgumentException('You must provide a context when attaching an image.');
        }

        try {
            DB::beginTransaction();

            $relationType = $this->validateRelationType($relation);

            $model = new (ImageLibrary::getImageModel())(array_merge(
                [
                    'disk' => $image->disk,
                ],
                $attributes,
                [
                    'model_type' => $this->getMorphClass(),
                    'model_id' => $this->getKey(),
                    'source_image_id' => $image->id,
                ]
            ));

            if ($relationType === MorphOne::class) {
                $this->{$relation}
                    ?->delete();
            } else {
                $context = $attributes['context'] instanceof ImageContext
                    ? $attributes['context']
                    : ImageLibrary::getImageContextByKey($attributes['context']);

                if ($context->getAllowsMultiple() === false) {
                    $this->{$relation}()
                        ->where('context', $context->getKey())
                        ->get()
                        ->each->delete();
                }
            }

            $this->{$relation}()->save($model);

            $this->unsetRelation($relation);

            DB::commit();

            return $model;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    private function validateRelationType(string $relation): string
    {
        if (! method_exists($this, $relation)) {
            throw new InvalidArgumentException("Relation {$relation} does not exist on the model.");
        }

        $instance = $this->{$relation}();

        if ($instance instanceof MorphOne) {
            return MorphOne::class;
        }

        if ($instance instanceof MorphMany) {
            return MorphMany::class;
        }

        throw new InvalidArgumentException("Relation {$relation} is not a valid MorphOne or MorphMany relation.");
    }
}
