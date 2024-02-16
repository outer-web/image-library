<?php

namespace Outerweb\ImageLibrary\Models\Traits;

use Illuminate\Support\Str;

trait GeneratesUuids
{
    public static function generateUuid(int $tries = 10): string
    {
        if ($tries === 0) {
            throw new \Exception('Could not generate a unique UUID');
        }

        $uuid = Str::uuid();

        if (self::where('uuid', $uuid)->exists()) {
            return self::generateUuid($tries - 1);
        }

        return $uuid;
    }

    public static function bootGeneratesUuids(): void
    {
        self::creating(function (self $image) {
            if (empty($image->uuid)) {
                $image->uuid = self::generateUuid();
            }
        });
    }
}
