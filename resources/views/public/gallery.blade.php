<x-layout class="min-h-screen bg-base-100">
    <x-slot name="title">{{ $photoGallery->name }} - Gallerie</x-slot>
    <x-slot name="description">Explorez la galerie de photos de {{ $photoGallery->name }}. Découvrez des moments capturés
        par Pinaton Photographie.</x-slot>

    <style>
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .slideshow-modal {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .slideshow-modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .cover-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            @if ($photoGallery->coverPhoto)
                <img src="{{ Storage::disk('photo')->url($photoGallery->coverPhoto->path) }}"
                    alt="Cover for {{ $photoGallery->name }}" class="cover-image shadow-lg">
            @elseif($photos->count() > 0)
                <img src="{{ Storage::disk('photo')->url($photos->first()->path) }}"
                    alt="Cover for {{ $photoGallery->name }}" class="cover-image shadow-lg">
            @endif

            <h1 class="text-3xl font-bold">{{ $photoGallery->name }}</h1>
            @if ($photoGallery->description)
                <p class="mt-2 text-gray-200">{{ $photoGallery->description }}</p>
            @endif

            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('public.download', $photoGallery->access_code) }}" class="btn btn-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Télécharger la galerie
                </a>
                <button id="slideshow-btn" class="btn btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7.5 3.75H6A2.25 2.25 0 0 0 3.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0 1 20.25 6v1.5m0 9V18A2.25 2.25 0 0 1 18 20.25h-1.5m-9 0H6A2.25 2.25 0 0 1 3.75 18v-1.5M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Diaporama
                </button>
            </div>
        </div>

        <!-- Photo Grid -->
        <div class="photo-grid">
            @foreach ($photos as $photo)
                <div class="photo-item">
                    <img src="{{ Storage::disk('photo')->url($photo->path) }}"
                        alt="{{ $photo->alt ?? 'Photo #' . $photo->id }}"
                        class="w-full h-64 object-cover rounded-lg shadow-md cursor-pointer hover:opacity-90 transition"
                        data-index="{{ $loop->index }}" onclick="openSlideshow({{ $loop->index }})">
                </div>
            @endforeach
        </div>

        <!-- Slideshow Modal -->
        <div id="slideshow-modal"
            class="slideshow-modal fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center">
            <button id="close-slideshow"
                class="absolute top-4 right-4 text-white text-4xl cursor-pointer hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
            <button id="prev-btn" class="absolute left-4 text-white text-5xl cursor-pointer hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
                </svg>
            </button>
            <button id="next-btn" class="absolute right-4 text-white text-5xl cursor-pointer hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                </svg>
            </button>

            <div id="slideshow-container" class="max-w-4xl max-h-screen p-4">
                <img id="current-slide" class="max-w-full max-h-[80vh] mx-auto" src="" alt="">
                <div class="text-white text-center mt-4">
                    <p id="slide-counter" class="text-sm"></p>
                    <p id="slide-alt" class="mt-2"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Slideshow functionality
        const photos = @json(
            $photos->map(function ($photo) {
                return [
                    'src' => Storage::disk('photo')->url($photo->path),
                    'alt' => $photo->alt ?? 'Photo #' . $photo->id,
                ];
            }));

        let currentIndex = 0;
        const modal = document.getElementById('slideshow-modal');
        const currentSlide = document.getElementById('current-slide');
        const slideCounter = document.getElementById('slide-counter');
        const slideAlt = document.getElementById('slide-alt');
        const totalPhotos = photos.length;

        function openSlideshow(index) {
            currentIndex = index;
            updateSlide();
            modal.classList.add('active');
        }

        function closeSlideshow() {
            modal.classList.remove('active');
        }

        function nextSlide() {
            currentIndex = (currentIndex + 1) % totalPhotos;
            updateSlide();
        }

        function prevSlide() {
            currentIndex = (currentIndex - 1 + totalPhotos) % totalPhotos;
            updateSlide();
        }

        function updateSlide() {
            currentSlide.src = photos[currentIndex].src;
            currentSlide.alt = photos[currentIndex].alt;
            slideCounter.textContent = `${currentIndex + 1} / ${totalPhotos}`;
            slideAlt.textContent = photos[currentIndex].alt;
        }

        // Event listeners
        document.getElementById('slideshow-btn').addEventListener('click', () => openSlideshow(0));
        document.getElementById('close-slideshow').addEventListener('click', closeSlideshow);
        document.getElementById('next-btn').addEventListener('click', nextSlide);
        document.getElementById('prev-btn').addEventListener('click', prevSlide);

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active')) return;

            if (e.key === 'Escape') closeSlideshow();
            if (e.key === 'ArrowRight') nextSlide();
            if (e.key === 'ArrowLeft') prevSlide();
        });
    </script>
</x-layout>
