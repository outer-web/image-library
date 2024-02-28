@php
	$attributes = $attributes->merge([
	    'data-image-library' => 'image',
	]);
@endphp

<picture>
	@if ($srcsetWebp)
		<source
			srcset="{{ $srcsetWebp }}"
			type="image/webp"
		/>
	@endif
	@if ($srcset && $image)
		<source
			srcset="{{ $srcset }}"
			type="{{ $image->mime_type }}"
		/>
	@endif
	<img
		src="{{ $src }}"
		sizes="1px"
		title="{{ $title }}"
		alt="{{ $alt }}"
		width="{{ $width }}"
		{{ $attributes }}
	/>
</picture>
