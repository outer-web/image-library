<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Contracts;

interface ConfiguresBreakpoints
{
    public static function sortedCases(): array;

    public function getLabel(): string;

    public function getMinWidth(): int;

    public function getMaxWidth(): ?int;

    public function getSlug(): string;
}
