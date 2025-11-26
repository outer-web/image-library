<script {{ $attributes }}>
(() => {
    const DEBOUNCE_DELAY = 500;
    const imageSelector = '[data-image-library="image"]';

    window.imageLibraryImages = window.imageLibraryImages || new Set();
    window.imageLibraryObserved = window.imageLibraryObserved || new Set();

    const debounce = (func, wait) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    };

    const setSizesAttribute = (image) => {
        if (!image.isConnected) return;

        let width = image.getBoundingClientRect().width;
        const style = getComputedStyle(image);
        const transform = style.transform;

        if (transform && transform !== 'none') {
            const match = transform.match(/^matrix\(([^,]+),/);
            if (match) width *= parseFloat(match[1]);
        }

        if (!width) return;

        const sizesValue = `${Math.ceil((width / window.innerWidth) * 100)}vw`;
        image.sizes = sizesValue;

        // Also update all <source> elements inside the same <picture>
        const picture = image.closest('picture');
        if (picture) {
            picture.querySelectorAll('source').forEach(source => {
                source.sizes = sizesValue;
            });
        }
    };

    const debouncedUpdate = debounce((image) => setSizesAttribute(image), DEBOUNCE_DELAY);

    const observeImage = (image) => {
        if (window.imageLibraryObserved.has(image)) return;

        window.imageLibraryObserved.add(image);

        if (!image.complete) image.addEventListener('load', () => debouncedUpdate(image), { once: true });

        debouncedUpdate(image);

        if (!window.imageLibraryIntersectionObserver) {
            window.imageLibraryIntersectionObserver = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) return;
                    debouncedUpdate(entry.target);
                });
            });
        }

        window.imageLibraryIntersectionObserver.observe(image);

        if ('ResizeObserver' in window) {
            if (!window.imageLibraryResizeObserver) {
                window.imageLibraryResizeObserver = new ResizeObserver(entries => {
                    entries.forEach(entry => debouncedUpdate(entry.target));
                });
            }

            window.imageLibraryResizeObserver.observe(image);
        }
    };

    const initImages = () => {
        document.querySelectorAll(imageSelector).forEach(image => {
            if (!window.imageLibraryImages.has(image)) {
                window.imageLibraryImages.add(image);
                observeImage(image);
            }
        });
    };

    const mutationObserver = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (!(node instanceof Element)) return;

                if (node.matches(imageSelector) && !window.imageLibraryImages.has(node)) {
                    window.imageLibraryImages.add(node);
                    observeImage(node);
                }

                node.querySelectorAll(imageSelector).forEach(image => {
                    if (!window.imageLibraryImages.has(image)) {
                        window.imageLibraryImages.add(image);
                        observeImage(image);
                    }
                });
            });
        });
    });

    mutationObserver.observe(document.body || document.documentElement, { childList: true, subtree: true });

    window.addEventListener('resize', debounce(() => {
        window.imageLibraryImages.forEach(debouncedUpdate);
    }, DEBOUNCE_DELAY));

    initImages();
})();
</script>
