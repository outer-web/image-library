@php
	$attributes = $attributes->merge([
	    'data-image-library' => 'image',
	    'data-image-library-id' => Str::uuid(),
	]);
@endphp

<img
	src="{{ $src }}"
	srcset="{{ $srcset }}"
	sizes="1px"
	title="{{ $title }}"
	alt="{{ $alt }}"
	width="{{ $width }}"
	{{ $attributes }}
/>
