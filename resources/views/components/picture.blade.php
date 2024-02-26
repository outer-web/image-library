@php
	$attributes = $attributes->merge([
	    'data-image-library' => 'image',
	]);
@endphp

<picture>
	<source
		srcset="{{ $srcsetWebp }}"
		type="image/webp"
	/>
	<source
		srcset="{{ $srcset }}"
		type="{{ $image->mime_type }}"
	/>
	<img
		src="{{ $src }}"
		sizes="1px"
		title="{{ $title }}"
		alt="{{ $alt }}"
		width="{{ $width }}"
		{{ $attributes }}
	/>
</picture>
