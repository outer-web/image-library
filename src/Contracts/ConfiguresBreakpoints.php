<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Contracts;

use BackedEnum;

interface ConfiguresBreakpoints extends BackedEnum
{
    public static function sortedCases(): array;

    public function getLabel(): string;

    public function getMinWidth(): int;

    public function getMaxWidth(): ?int;

    public function getSlug(): string;
}
