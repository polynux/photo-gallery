<x-layout>
    <x-slot name="title">{{ $photoGallery->name }} - Galerie</x-slot>
    <x-slot name="description">Explorez la galerie de photos de {{ $photoGallery->name }}. Découvrez des moments capturés par Pinaton Photographie.</x-slot>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-display {
            font-family: 'Playfair Display', serif;
        }
        .masonry-grid {
            column-count: 1;
            column-gap: 1.5rem;
        }
        @media (min-width: 640px) {
            .masonry-grid { column-count: 2; }
        }
        @media (min-width: 1024px) {
            .masonry-grid { column-count: 3; }
        }
        .masonry-item {
            break-inside: avoid;
            margin-bottom: 1.5rem;
        }
        .slideshow-modal {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
        }
        .slideshow-modal.active {
            opacity: 1;
            pointer-events: auto;
        }
        .slide-image {
            transition: transform 0.4s ease, opacity 0.4s ease;
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .slide-image.prev {
            transform: translateX(-100%);
            opacity: 0;
        }
        .slide-image.current {
            transform: translateX(0);
            opacity: 1;
        }
        .slide-image.next {
            transform: translateX(100%);
            opacity: 0;
        }
        .slide-image.sliding-out-left {
            transform: translateX(-100%);
            opacity: 0;
        }
        .slide-image.sliding-out-right {
            transform: translateX(100%);
            opacity: 0;
        }
        .slide-image.sliding-in-left {
            animation: slideInFromLeft 0.4s ease forwards;
        }
        .slide-image.sliding-in-right {
            animation: slideInFromRight 0.4s ease forwards;
        }
        @keyframes slideInFromLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideInFromRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .image-container {
            position: relative;
            width: 100%;
            height: 80vh;
            overflow: hidden;
        }
    </style>

    <!-- Gallery Header -->
    <section class="pt-32 pb-12 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="max-w-4xl mx-auto text-center">
                @if ($photoGallery->coverPhoto)
                    <div class="relative rounded-2xl overflow-hidden mb-8 shadow-2xl mt-16">
                        <img src="{{ Storage::disk('thumbnails')->url($photoGallery->coverPhoto->path) }}"
                            alt="Cover for {{ $photoGallery->name }}" 
                            class="w-full h-80 md:h-[32rem] object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
                            <h1 class="text-3xl md:text-4xl font-display font-bold mb-2">{{ $photoGallery->name }}</h1>
                            @if ($photoGallery->description)
                                <p class="text-white/80">{{ $photoGallery->description }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="mb-8">
                        <p class="text-sm font-medium tracking-[0.2em] uppercase mb-4 text-gray-500">Galerie Privée</p>
                        <h1 class="text-4xl md:text-5xl font-display font-bold text-gray-900 mb-4">{{ $photoGallery->name }}</h1>
                        @if ($photoGallery->description)
                            <p class="text-gray-600">{{ $photoGallery->description }}</p>
                        @endif
                    </div>
                @endif

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('public.download', $photoGallery->access_code) }}" 
                       class="inline-flex items-center px-6 py-3 bg-gray-900 text-white rounded-full font-medium transition-all hover:shadow-lg hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Télécharger la galerie
                    </a>
                    <button id="slideshow-btn" 
                            class="group cursor-pointer inline-flex items-center px-6 py-3 border-2 border-gray-900 text-gray-900 rounded-full font-medium transition-all hover:bg-gray-100 hover:shadow-lg hover:scale-105">
                        <svg class="w-5 h-5 mr-2 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z"/>
                        </svg>
                        Diaporama
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Gradient Transition -->
    <div class="h-24 bg-gradient-to-b from-gray-50 to-white"></div>

    <!-- Photo Grid -->
    <section class="pb-12 bg-white">
        <div class="container mx-auto px-6">
            <div class="masonry-grid">
                @foreach ($photos as $photo)
                    <div class="masonry-item group relative overflow-hidden rounded-lg shadow-md cursor-pointer hover-lift"
                         onclick="openSlideshow({{ $loop->index }})">
                        <img src="{{ Storage::disk('thumbnails')->url($photo->path) }}"
                            alt="{{ $photo->alt ?? 'Photo #' . $photo->id }}"
                            class="w-full h-auto object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                            <div class="p-4 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                <p class="text-white font-medium">{{ $photo->alt ?? 'Photo #' . $photo->id }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

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

            <div id="slideshow-container" class="max-w-5xl w-full p-4 relative">
                <div class="image-container relative overflow-hidden rounded-lg">
                    <img id="current-slide" class="slide-image current" src="" alt="">
                </div>
                <div class="text-white text-center mt-6">
                    <p id="slide-counter" class="text-sm font-medium tracking-wider"></p>
                    <p id="slide-alt" class="mt-2 text-white/80"></p>
                </div>
            </div>
        </div>
    </section>

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
        let isAnimating = false;
        const modal = document.getElementById('slideshow-modal');
        const currentSlide = document.getElementById('current-slide');
        const slideCounter = document.getElementById('slide-counter');
        const slideAlt = document.getElementById('slide-alt');
        const totalPhotos = photos.length;

        function openSlideshow(index) {
            currentIndex = index;
            currentSlide.src = photos[currentIndex].src;
            currentSlide.alt = photos[currentIndex].alt;
            slideCounter.textContent = `${currentIndex + 1} / ${totalPhotos}`;
            slideAlt.textContent = photos[currentIndex].alt;
            currentSlide.className = 'slide-image current';
            modal.classList.add('active');
        }

        function closeSlideshow() {
            modal.classList.remove('active');
        }

        function nextSlide() {
            if (totalPhotos <= 1 || isAnimating) return;
            isAnimating = true;
            
            // Slide current image to the left (exiting)
            currentSlide.classList.remove('current');
            currentSlide.classList.add('sliding-out-left');
            
            setTimeout(() => {
                currentIndex = (currentIndex + 1) % totalPhotos;
                updateSlide();
                // New image enters from the right
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
            
            // Slide current image to the right (exiting)
            currentSlide.classList.remove('current');
            currentSlide.classList.add('sliding-out-right');
            
            setTimeout(() => {
                currentIndex = (currentIndex - 1 + totalPhotos) % totalPhotos;
                updateSlide();
                // New image enters from the left
                currentSlide.classList.remove('sliding-out-right');
                currentSlide.classList.add('sliding-in-left');
                
                setTimeout(() => {
                    currentSlide.classList.remove('sliding-in-left');
                    currentSlide.classList.add('current');
                    isAnimating = false;
                }, 400);
            }, 400);
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
