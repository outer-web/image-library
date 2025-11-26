@php
    $attributes = $attributes->merge([
        'src' => $image->sourceImage->url(),
        'alt' => $image->alt_text ?? $image->sourceImage->alt_text,
        'sizes' => '1px',
        'data-image-library' => 'image',
        'data-image-library-id' => $image->uuid,
    ]);
@endphp

<picture>
    @foreach ($sources as $source)
        <source
            media="{{ $source->media }}"
            srcset="{{ $source->srcset }}"
            type="{{ $source->type }}"
            sizes="1px"
        />
    @endforeach
    <img {{ $attributes }} />
</picture>
