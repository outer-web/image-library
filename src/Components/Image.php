<?php

declare(strict_types=1);

namespace Outerweb\ImageLibrary\Components;

use Closure;
use Illuminate\View\Component;
use Illuminate\View\View;
use Outerweb\ImageLibrary\Contracts\ConfiguresBreakpoints;
use Outerweb\ImageLibrary\Facades\ImageLibrary;
use Outerweb\ImageLibrary\Models\Image as ImageModel;

class Image extends Component
{
    public function __construct(
        public ?ImageModel $image = null,
    ) {}

    public function shouldRender(): bool
    {
        return ! is_null($this->image);
    }

    public function render(): View|Closure|string
    {
        if ($this->image->context->getUseBreakpoints()) {
            $useBreakpoints = true;

            $sources = collect(ImageLibrary::getBreakpointEnum()::sortedCases())
                ->map(function (ConfiguresBreakpoints $case): array {
                    return array_filter([
                        (object) [
                            'media' => $this->getMediaQueryForBreakpoint($case),
                            'srcset' => $this->getSrcsetForBreakpoint($case),
                            'type' => $this->image->sourceImage->mime_type,
                        ],
                        $this->image->context->getGenerateWebP()
                            ? (object) [
                                'media' => $this->getMediaQueryForBreakpoint($case),
                                'srcset' => $this->getSrcsetForBreakpoint($case, 'webp'),
                                'type' => 'image/webp',
                            ]
                            : null,
                    ]);
                })
                ->flatten(1);
        } else {
            $useBreakpoints = false;

            $sources = array_filter([
                (object) [
                    'media' => '',
                    'srcset' => $this->image->urlForBreakpoint(null),
                    'type' => $this->image->sourceImage->mime_type,
                ],
                $this->image->context->getGenerateWebP()
                    ? (object) [
                        'media' => '',
                        'srcset' => $this->image->urlForBreakpoint(null, 'webp'),
                        'type' => 'image/webp',
                    ]
                    : null,
            ]);
        }

        return view('image-library::components.image', [
            'sources' => $sources,
            'useBreakpoints' => $useBreakpoints,
        ]);
    }

    private function getMediaQueryForBreakpoint(ConfiguresBreakpoints $breakpoint): string
    {
        $conditions = [];

        if (array_search($breakpoint, ImageLibrary::getBreakpointEnum()::sortedCases()) !== 0) {
            $conditions[] = '(min-width: '.$breakpoint->getMinWidth().'px)';
        }

        if (! is_null($breakpoint->getMaxWidth())) {
            $conditions[] = '(max-width: '.$breakpoint->getMaxWidth().'px)';
        }

        return implode(' and ', $conditions);
    }

    private function getSrcsetForBreakpoint(ConfiguresBreakpoints $breakpoint, ?string $extension = null): string
    {
        if (! $this->image->context->getGenerateResponsiveVersions()) {
            return $this->image->urlForBreakpoint($breakpoint, $extension);
        }

        return $this->image->getResponsiveRelativePathsForBreakpoint($breakpoint, $extension)
            ->map(function (string $path) use ($breakpoint): string {
                if (preg_match('/_w(\d+)\./', $path, $m)) {
                    $width = (int) $m[1];
                } else {
                    $width = $this->image->context->getMaxWidth($breakpoint)
                        ?? $this->image->sourceImage->width;
                }

                $url = $this->image->urlForRelativePath($path);

                return "{$url} {$width}w";
            })
            ->implode(', ');
    }
}
