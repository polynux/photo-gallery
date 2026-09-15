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
        initSlideshow(slideshowState);
    }

    initPhotoSelection();
}

function initSlideshow(sections) {
    const allPhotos = [];

    sections.forEach(section => {
        section.photos.forEach(photo => {
            allPhotos.push(photo);
        });
    });

    let currentIndex = 0;
    let isAnimating = false;
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
        currentSlide.className = 'slide-image current';
        modal.classList.add('active');
    }

    function closeSlideshow() {
        modal.classList.remove('active');
    }

    function nextSlide() {
        if (totalPhotos <= 1 || isAnimating) return;
        isAnimating = true;

        currentSlide.classList.remove('current');
        currentSlide.classList.add('sliding-out-left');

        setTimeout(() => {
            currentIndex = (currentIndex + 1) % totalPhotos;
            updateSlide();
            currentSlide.classList.remove('sliding-out-left');
            currentSlide.classList.add('sliding-in-right');

            setTimeout(() => {
                currentSlide.classList.remove('sliding-in-right');
                currentSlide.classList.add('current');
                isAnimating = false;
            }, 400);
        }, 400);
    }

    function prevSlide() {
        if (totalPhotos <= 1 || isAnimating) return;
        isAnimating = true;

        currentSlide.classList.remove('current');
        currentSlide.classList.add('sliding-out-right');

        setTimeout(() => {
            currentIndex = (currentIndex - 1 + totalPhotos) % totalPhotos;
            updateSlide();
            currentSlide.classList.remove('sliding-out-right');
            currentSlide.classList.add('sliding-in-left');

            setTimeout(() => {
                currentSlide.classList.remove('sliding-in-left');
                currentSlide.classList.add('current');
                isAnimating = false;
            }, 400);
        }, 400);
    }

    function showSpinner() {
        slideSpinner.classList.add('active');
    }

    function hideSpinner() {
        slideSpinner.classList.remove('active');
    }

    function updateSlide() {
        const src = allPhotos[currentIndex].src;

        if (currentSlide.src !== src) {
            showSpinner();
        }

        currentSlide.src = src;
        currentSlide.alt = allPhotos[currentIndex].alt;
        slideCounter.textContent = `${currentIndex + 1} / ${totalPhotos}`;
        slideAlt.textContent = allPhotos[currentIndex].alt;
    }

    document.getElementById('slideshow-btn').addEventListener('click', () => {
        if (totalPhotos > 0) {
            openSlideshow(sections[0].id, 0);
        }
    });
    document.getElementById('close-slideshow').addEventListener('click', closeSlideshow);
    document.getElementById('next-btn').addEventListener('click', nextSlide);
    document.getElementById('prev-btn').addEventListener('click', prevSlide);

    currentSlide.addEventListener('load', hideSpinner);
    currentSlide.addEventListener('error', hideSpinner);

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