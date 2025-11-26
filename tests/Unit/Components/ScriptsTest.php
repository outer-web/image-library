<?php

declare(strict_types=1);

use Illuminate\View\View;
use Outerweb\ImageLibrary\Components\Scripts;

describe('Scripts Component', function (): void {
    it('can be constructed', function (): void {
        $component = new Scripts();

        expect($component)
            ->toBeInstanceOf(Scripts::class);
    });

    it('renders the correct view', function (): void {
        $component = new Scripts();

        $view = $component->render();

        expect($view)
            ->toBeInstanceOf(View::class);

        expect($view->getName())
            ->toBe('image-library::components.scripts');
    });
});
