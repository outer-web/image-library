<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Enums;

use Illuminate\Support\Str;
use Outerweb\ImageLibrary\Contracts\ConfiguresBreakpoints;

enum Breakpoint: string implements ConfiguresBreakpoints
{
    case Small = 'sm';
    case Medium = 'md';
    case Large = 'lg';
    case ExtraLarge = 'xl';
    case ExtraExtraLarge = '2xl';

    public static function sortedCases(): array
    {
        return collect(self::cases())
            ->sort(fn ($a, $b) => $a->getMinWidth() <=> $b->getMinWidth())
            ->all();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Small => __('image-library::breakpoints.sm'),
            self::Medium => __('image-library::breakpoints.md'),
            self::Large => __('image-library::breakpoints.lg'),
            self::ExtraLarge => __('image-library::breakpoints.xl'),
            self::ExtraExtraLarge => __('image-library::breakpoints.2xl'),
        };
    }

    public function getMinWidth(): int
    {
        return match ($this) {
            self::Small => 640,
            self::Medium => 768,
            self::Large => 1024,
            self::ExtraLarge => 1280,
            self::ExtraExtraLarge => 1536,
        };
    }

    public function getMaxWidth(): ?int
    {
        $index = array_search($this, self::sortedCases(), true);

        $next = self::sortedCases()[$index + 1] ?? null;

        return $next ? $next->getMinWidth() - 1 : null;
    }

    public function getSlug(): string
    {
        return Str::of($this->value)
            ->lower()
            ->slug()
            ->toString();
    }
}
