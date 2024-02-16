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
		onload="if(!(width=this.getBoundingClientRect().width))return;this.onload=null;this.sizes=Math.ceil(width/window.innerWidth*100)+'vw';"
	/>
</picture>
