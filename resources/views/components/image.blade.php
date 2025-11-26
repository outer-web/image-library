@php
    $useBreakpoints = $useBreakpoints ?? true;
    $src = $useBreakpoints ? $image->sourceImage->url() : $image->urlForBreakpoint();
    
    $attributes = $attributes->merge([
        'src' => $src,
        'alt' => $image->alt_text ?? $image->sourceImage->alt_text,
        'sizes' => $useBreakpoints ? '1px' : null,
        'data-image-library' => 'image',
        'data-image-library-id' => $image->uuid,
    ]);
@endphp

<picture>
    @foreach ($sources as $source)
        <source
            @if ($useBreakpoints && $source->media) media="{{ $source->media }}" @endif
            srcset="{{ $source->srcset }}"
            type="{{ $source->type }}"
            @if ($useBreakpoints) sizes="1px" @endif
        />
    @endforeach
    <img {{ $attributes }} />
</picture>
