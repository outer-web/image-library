<script>
	(() => {
		const imageSelector = '[data-image-library="image"]';
		const imageIdDataAttribute = 'data-image-library-id';
		window.imageLibraryImages = window.imageLibraryImages || [];

		const init = () => {
			if (window.imageLibraryAbortController) {
				window.imageLibraryAbortController.abort();
			}

			window.imageLibraryAbortController = new AbortController();

			const debounce = (callback, wait) => {
				let timeout;
				return (...args) => {
					const context = this;
					clearTimeout(timeout);
					timeout = setTimeout(() => callback.apply(context, args), wait);
				};
			};

			const setSizesAttribute = (image, event) => {
				if (!(width = image.getBoundingClientRect().width)) return;
				image.sizes = Math.ceil(width / window.innerWidth * 100) + 'vw';
			};

			window.imageLibraryImages.forEach((image) => {
				image.addEventListener('load', function() {
					setSizesAttribute(image);
				}, {
					signal: window.imageLibraryAbortController.signal,
					once: true
				});
				window.addEventListener('resize', function() {
					debounce(() => setSizesAttribute(
							image),
						100);
				}, {
					signal: window.imageLibraryAbortController.signal
				});
				setSizesAttribute(image);
			});
		};

		function checkIfImg(toCheck) {
			let imagesInNode = toCheck.querySelectorAll(imageSelector);
			let imagesToAppend = [];

			for (let image of imagesInNode) {
				if (!window.imageLibraryImages.find((img) => img.getAttribute(imageIdDataAttribute) === image
						.getAttribute(
							imageIdDataAttribute))) {
					imagesToAppend.push(image);
				}
			}

			if (imagesToAppend.length === 0) return;

			window.imageLibraryImages = window.imageLibraryImages.concat(imagesToAppend);

			init();

			setTimeout(() => init(), {{ config('blade_script_init_delay', 300) }});
		};

		var observer = new MutationObserver(function(mutations) {
			mutations.forEach(mutation => checkIfImg(mutation.target));
		});

		observer.observe(document, {
			childList: true,
			subtree: true
		});

		window.imageLibraryImages = Array.from(document.querySelectorAll(imageSelector));

		init();
	})();
</script>
