const slideshowState = readSlideshowState();

function readSlideshowState() {
    const script = document.querySelector('script[data-gallery-state]');

    if (! script) {
        return null;
    }

    try {
        return JSON.parse(script.textContent);
    } catch {
        return null;
    }
}

export function initGalleryPage() {
    if (slideshowState) {
        initLazyLoading(slideshowState);
        initSlideshow(slideshowState);
    }

    initPhotoSelection();
}

function initLazyLoading(state) {
    const lazyImages = Array.from(document.querySelectorAll('img.js-lazy-img[data-src]'));

    if (lazyImages.length === 0 || ! ('IntersectionObserver' in window)) {
        return;
    }

    const rootMargin = `${state.lazyRootMargin ?? 800}px`;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            loadImage(entry.target);
            observer.unobserve(entry.target);
        });
    }, { rootMargin });

    lazyImages.forEach((img) => {
        img.addEventListener('load', () => hideSpinner(img), { once: true });
        img.addEventListener('error', () => hideSpinner(img), { once: true });
        observer.observe(img);
    });

    function loadImage(img) {
        showSpinner(img);
        img.src = img.dataset.src;
        delete img.dataset.src;
    }

    function showSpinner(img) {
        img.parentElement.querySelector('.js-lazy-spinner')?.classList.add('opacity-100');
    }

    function hideSpinner(img) {
        img.parentElement.querySelector('.js-lazy-spinner')?.classList.remove('opacity-100');
    }
}

function initSlideshow(state) {
    const sections = state.sections ?? [];
    const allPhotos = [];

    sections.forEach(section => {
        section.photos.forEach(photo => {
            allPhotos.push(photo);
        });
    });

    let currentIndex = 0;
    let isAnimating = false;
    let pendingReveal = null;
    let slideFailed = false;
    const modal = document.getElementById('slideshow-modal');
    const currentSlide = document.getElementById('current-slide');
    const slideCounter = document.getElementById('slide-counter');
    const slideAlt = document.getElementById('slide-alt');
    const slideSpinner = document.getElementById('slide-spinner');
    const totalPhotos = allPhotos.length;

    if (! modal || totalPhotos === 0) {
        return;
    }

    function openSlideshow(sectionId, localIndex) {
        let offset = 0;
        for (const section of sections) {
            if (section.id === sectionId) {
                currentIndex = offset + localIndex;
                break;
            }
            offset += section.photos.length;
        }

        updateSlide();
        currentSlide.classList.remove('sliding-out-left', 'sliding-out-right', 'sliding-in-left', 'sliding-in-right');
        currentSlide.classList.add('current');
        modal.classList.add('active');
    }

    function closeSlideshow() {
        modal.classList.remove('active');
    }

    function navigateTo(nextIndex, outClass, inClass) {
        if (totalPhotos <= 1 || isAnimating) return;
        isAnimating = true;

        currentSlide.classList.remove('current');
        currentSlide.classList.add(outClass);

        setTimeout(() => {
            currentIndex = nextIndex;
            updateSlide();

            const finish = () => {
                currentSlide.classList.remove(outClass, 'slide-loading');
                currentSlide.classList.add(inClass);

                setTimeout(() => {
                    currentSlide.classList.remove(inClass);
                    currentSlide.classList.add('current');
                    isAnimating = false;
                }, 400);
            };

            if (pendingReveal) {
                pendingReveal.ready.then(finish);
            } else {
                finish();
            }
        }, 400);
    }

    function nextSlide() {
        navigateTo((currentIndex + 1) % totalPhotos, 'sliding-out-left', 'sliding-in-right');
    }

    function prevSlide() {
        navigateTo((currentIndex - 1 + totalPhotos) % totalPhotos, 'sliding-out-right', 'sliding-in-left');
    }

    function showSpinner() {
        slideSpinner.classList.add('active');
    }

    function hideSpinner() {
        slideSpinner.classList.remove('active');
    }

    function preload(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(src);
            img.onerror = () => reject(new Error(`Failed to load slide: ${src}`));
            img.src = src;
        });
    }

    function updateSlide() {
        const photo = allPhotos[currentIndex];
        const src = photo.src;

        slideCounter.textContent = `${currentIndex + 1} / ${totalPhotos}`;
        slideAlt.textContent = photo.alt;

        if (pendingReveal) {
            pendingReveal.abort();
        }

        if (slideFailed || currentSlide.src !== src) {
            slideFailed = false;
            showSpinner();
            currentSlide.classList.add('slide-loading');
            pendingReveal = revealWhenLoaded(src);
        } else {
            pendingReveal = null;
        }
    }

    function revealWhenLoaded(src) {
        let aborted = false;
        let readyResolve;
        const ready = new Promise((resolve) => {
            readyResolve = resolve;
        });

        preload(src).then(() => {
            if (aborted || allPhotos[currentIndex].src !== src) {
                return;
            }

            currentSlide.src = src;
            currentSlide.alt = allPhotos[currentIndex].alt;
            currentSlide.classList.remove('slide-loading');
            hideSpinner();
        }).catch(() => {
            if (aborted) {
                return;
            }

            slideFailed = true;
            currentSlide.classList.remove('slide-loading');
            hideSpinner();
        }).finally(() => {
            readyResolve();
        });

        return {
            ready,
            abort() {
                aborted = true;
                readyResolve();
            },
        };
    }

    document.getElementById('slideshow-btn').addEventListener('click', () => {
        if (totalPhotos > 0) {
            openSlideshow(sections[0].id, 0);
        }
    });
    document.getElementById('close-slideshow').addEventListener('click', closeSlideshow);
    document.getElementById('next-btn').addEventListener('click', nextSlide);
    document.getElementById('prev-btn').addEventListener('click', prevSlide);

    modal.addEventListener('click', (e) => {
        if (e.target.closest('#slideshow-container') || e.target.closest('button')) {
            return;
        }

        closeSlideshow();
    });

    document.querySelectorAll('.js-slideshow-item').forEach(item => {
        item.addEventListener('click', () => {
            openSlideshow(Number(item.dataset.sectionId), Number(item.dataset.photoIndex));
        });
    });

    document.addEventListener('keydown', (e) => {
        if (! modal.classList.contains('active')) return;

        if (e.key === 'Escape') closeSlideshow();
        if (e.key === 'ArrowRight') nextSlide();
        if (e.key === 'ArrowLeft') prevSlide();
    });
}

function initPhotoSelection() {
    const selectionBar = document.getElementById('selection-bar');

    if (! selectionBar) {
        return;
    }

    const selectionCounter = document.getElementById('selection-counter');
    const selectAllBtn = document.getElementById('select-all-btn');
    const deselectAllBtn = document.getElementById('deselect-all-btn');
    const selectionForm = document.getElementById('selection-download-form');
    const selectionIdsContainer = document.getElementById('selection-ids-container');
    const selectionDownloadBtn = document.getElementById('selection-download-btn');
    const selectionDownloadLabel = document.getElementById('selection-download-label');
    const photoCheckboxes = Array.from(document.querySelectorAll('.js-photo-checkbox'));
    const photoItemsById = {};
    document.querySelectorAll('.masonry-item[data-photo-id]').forEach(item => {
        photoItemsById[Number(item.dataset.photoId)] = item;
    });

    const selection = new Set();

    function updateSelectionUi() {
        const count = selection.size;

        selectionBar.classList.toggle('hidden', count === 0);

        if (count === 0) {
            selectionCounter.textContent = '0 photo sélectionnée';
        } else if (count === 1) {
            selectionCounter.textContent = '1 photo sélectionnée';
        } else {
            selectionCounter.textContent = `${count} photos sélectionnées`;
        }

        selectionDownloadBtn.disabled = count === 0;
        selectionDownloadLabel.textContent = count > 0
            ? `Télécharger la sélection (${count})`
            : 'Télécharger la sélection';

        photoCheckboxes.forEach(checkbox => {
            const checked = selection.has(Number(checkbox.value));
            checkbox.checked = checked;
            const item = photoItemsById[Number(checkbox.value)];
            if (item) {
                item.classList.toggle('selected', checked);
            }
            checkbox.classList.toggle('checked', checked);
        });
    }

    function togglePhoto(photoId, checked) {
        if (checked) {
            selection.add(photoId);
        } else {
            selection.delete(photoId);
        }
        updateSelectionUi();
    }

    photoCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            togglePhoto(Number(checkbox.value), checkbox.checked);
        });
    });

    selectAllBtn.addEventListener('click', () => {
        photoCheckboxes.forEach(checkbox => selection.add(Number(checkbox.value)));
        updateSelectionUi();
    });

    deselectAllBtn.addEventListener('click', () => {
        selection.clear();
        updateSelectionUi();
    });

    selectionForm.addEventListener('submit', () => {
        if (selection.size === 0) {
            return false;
        }

        selectionIdsContainer.innerHTML = '';
        selection.forEach(photoId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'photo_ids[]';
            input.value = photoId;
            selectionIdsContainer.appendChild(input);
        });

        selectionDownloadBtn.disabled = true;
        selectionDownloadLabel.textContent = 'Préparation…';
    });
}

initGalleryPage();