<img
	src="{{ $src }}"
	srcset="{{ $srcset }}"
	sizes="1px"
	title="{{ $title }}"
	alt="{{ $alt }}"
	width="{{ $width }}"
	{{ $attributes }}
	onload="if(!(width=this.getBoundingClientRect().width))return;this.onload=null;this.sizes=Math.ceil(width/window.innerWidth*100)+'vw';"
/>
