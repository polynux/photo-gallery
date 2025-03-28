<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $photoGallery->name }} - Gallery</title>
    @vite('resources/css/app.css')
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
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold">{{ $photoGallery->name }}</h1>
            @if($photoGallery->description)
                <p class="mt-2 text-gray-600">{{ $photoGallery->description }}</p>
            @endif

            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('public.download', $photoGallery->access_code) }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L10 12.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Download All Photos
                </a>
                <button id="slideshow-btn" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>
                    Start Slideshow
                </button>
            </div>
        </div>

        <!-- Photo Grid -->
        <div class="photo-grid">
            @foreach($photos as $photo)
                <div class="photo-item">
                    <img
                        src="{{ Storage::url($photo->path) }}"
                        alt="{{ $photo->alt ?? 'Photo #' . $photo->id }}"
                        class="w-full h-64 object-cover rounded-lg shadow-md cursor-pointer hover:opacity-90 transition"
                        data-index="{{ $loop->index }}"
                        onclick="openSlideshow({{ $loop->index }})"
                    >
                </div>
            @endforeach
        </div>

        <!-- Slideshow Modal -->
        <div id="slideshow-modal" class="slideshow-modal fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center">
            <button id="close-slideshow" class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300">&times;</button>
            <button id="prev-btn" class="absolute left-4 text-white text-5xl hover:text-gray-300">&larr;</button>
            <button id="next-btn" class="absolute right-4 text-white text-5xl hover:text-gray-300">&rarr;</button>

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
        const photos = @json($photos->map(function($photo) {
            return [
                'src' => Storage::url($photo->path),
                'alt' => $photo->alt ?? 'Photo #' . $photo->id
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
</body>
</html>
