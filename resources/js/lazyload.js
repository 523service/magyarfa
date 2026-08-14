/**
 * LQIP (Low Quality Image Placeholder) lazy loader using IntersectionObserver.
 *
 * Images must have:
 *   src       = base64svg placeholder
 *   data-src  = real image URL
 *   data-srcset = real srcset (optional)
 *   class="lazy"
 */

function loadImage(img) {
    if (img.dataset.srcset) {
        img.srcset = img.dataset.srcset;
    }

    if (img.dataset.src) {
        img.src = img.dataset.src;
    }

    img.classList.remove('lazy');
}

function initLazyLoad() {
    const lazyImages = Array.from(document.querySelectorAll('img.lazy'));

    if (!lazyImages.length) {
        return;
    }

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        loadImage(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            },
            {
                rootMargin: '200px 0px',
                threshold: 0.01,
            }
        );

        lazyImages.forEach((img) => observer.observe(img));
    } else {
        // Fallback for browsers without IntersectionObserver support
        lazyImages.forEach(loadImage);
    }
}

document.addEventListener('DOMContentLoaded', initLazyLoad);