<?php

declare(strict_types=1);

use Outerweb\ImageLibrary\Enums\Breakpoint;

dataset('breakpoints', function () {
    return Breakpoint::cases();
});

it('has a way to get cases sorted by minimum width', function () {
    $sorted = Breakpoint::sortedCases();

    expect(Breakpoint::sortedCases())
        ->toBeArray()
        ->toHaveCount(count(Breakpoint::cases()));

    for ($i = 0; $i < count($sorted) - 1; $i++) {
        expect($sorted[$i]->getMinWidth())
            ->toBeLessThan($sorted[$i + 1]->getMinWidth());
    }
});

test('each breakpoint has a label', function (Breakpoint $breakpoint) {
    expect($breakpoint->getLabel())
        ->toBeString()
        ->not->toBeEmpty();
})
    ->with('breakpoints');

test('each breakpoint has a minimum width', function (Breakpoint $breakpoint) {
    expect($breakpoint->getMinWidth())
        ->toBeInt()
        ->toBeGreaterThan(0);
})
    ->with('breakpoints');

test('each breakpoint has a maximum width except the last one', function (Breakpoint $breakpoint) {
    if ($breakpoint === array_last(Breakpoint::sortedCases())) {
        expect($breakpoint->getMaxWidth())
            ->toBeNull();
    } else {
        expect($breakpoint->getMaxWidth())
            ->toBeInt()
            ->toBeGreaterThan($breakpoint->getMinWidth());
    }
})
    ->with('breakpoints');

test('each breakpoint has a slug', function (Breakpoint $breakpoint) {
    expect($breakpoint->getSlug())
        ->toBeString()
        ->not->toBeEmpty();
})
    ->with('breakpoints');
