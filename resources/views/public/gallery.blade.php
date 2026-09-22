<x-layout robots="noindex, nofollow">
    <x-slot name="title">{{ $photoGallery->name }} - Galerie</x-slot>
    <x-slot name="description">Explorez la galerie de photos de {{ $photoGallery->name }}. Découvrez des moments capturés par Pinaton Photographie.</x-slot>

    @vite('resources/js/gallery.js')

    <script type="application/json" data-gallery-state>{!! json_encode($slideshowData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

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
        @media (min-width: 1440px) {
            .masonry-grid { column-count: 4; }
        }
        @media (min-width: 1920px) {
            .masonry-grid { column-count: 5; }
        }
        .masonry-item {
            break-inside: avoid;
            margin-bottom: 1.5rem;
        }
        .masonry-item.selected {
            outline: 3px solid #111827;
            outline-offset: 2px;
        }
        .photo-select-checkbox {
            opacity: 1;
            background: radial-gradient(circle, rgba(0, 0, 0, 0.45) 40%, rgba(0, 0, 0, 0) 75%);
            transition: opacity 0.2s ease, filter 0.2s ease;
        }
        .photo-select-checkbox:hover {
            filter: brightness(1.35);
        }
        @media (hover: hover) and (pointer: fine) {
            .photo-select-checkbox {
                opacity: 0;
            }
            .masonry-item:hover .photo-select-checkbox,
            .photo-select-checkbox.checked {
                opacity: 1;
            }
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
        .slide-image.slide-loading {
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
        .slide-spinner {
            position: absolute;
            inset: 0;
            z-index: 10;
            display: none;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .slide-spinner.active {
            display: flex;
        }
        .spinner-ring {
            width: 3rem;
            height: 3rem;
            border: 3px solid rgba(255, 255, 255, 0.25);
            border-top-color: rgba(255, 255, 255, 0.9);
            border-radius: 9999px;
            animation: spinnerSpin 0.8s linear infinite;
        }
        @keyframes spinnerSpin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- Gallery Header -->
    <section class="pt-32 pb-12 bg-gray-50">
        <div class="container mx-auto px-6">
            <div class="max-w-4xl mx-auto text-center">
                @if ($photoGallery->coverPhoto)
                    <div class="relative rounded-2xl overflow-hidden mb-8 shadow-2xl mt-16">
                        <img src="{{ route('display.show', ['gallery' => $photoGallery->coverPhoto->photo_gallery_id, 'photo' => basename($photoGallery->coverPhoto->path)]) }}"
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
            <div id="selection-bar"
                class="hidden sticky top-20 z-30 mb-6 flex flex-wrap items-center justify-between gap-4 rounded-full bg-gray-900 px-6 py-3 text-white shadow-lg">
                <span id="selection-counter" class="font-medium">0 photo sélectionnée</span>
                <div class="flex items-center gap-2">
                    <button type="button" id="select-all-btn"
                        class="cursor-pointer rounded-full px-4 py-1.5 text-sm font-medium transition-colors hover:bg-white/10">
                        Tout sélectionner
                    </button>
                    <button type="button" id="deselect-all-btn"
                        class="cursor-pointer rounded-full px-4 py-1.5 text-sm font-medium transition-colors hover:bg-white/10">
                        Tout désélectionner
                    </button>
                    <form id="selection-download-form"
                        action="{{ route('public.download-selection', $photoGallery->access_code) }}"
                        method="POST">
                        @csrf
                        <div id="selection-ids-container" class="hidden"></div>
                        <button type="submit" id="selection-download-btn" disabled
                            class="ml-2 inline-flex cursor-pointer items-center rounded-full bg-white px-5 py-1.5 text-sm font-medium text-gray-900 transition-all disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            <span id="selection-download-label">Télécharger la sélection</span>
                        </button>
                    </form>
                </div>
            </div>
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif
            @foreach ($photoGallery->sections as $section)
                <div class="mb-12">
                    @if ($photoGallery->sections->count() > 1)
                        <h2 class="text-2xl font-display font-bold text-gray-900 mb-6">{{ $section->name }}</h2>
                    @endif
                    <div class="masonry-grid">
                        @foreach ($section->photos as $photo)
                            <div class="masonry-item group relative overflow-hidden rounded-lg shadow-md cursor-pointer hover-lift js-slideshow-item"
                                 data-section-id="{{ $section->id }}"
                                 data-photo-index="{{ $loop->index }}"
                                 data-photo-id="{{ $photo->id }}">
                                <div class="photo-placeholder relative w-full bg-gray-100 overflow-hidden">
                                    <img @if ($photo->width && $photo->height)
                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                            data-src="{{ route('thumbnails.show', ['gallery' => $photo->photo_gallery_id, 'photo' => basename($photo->path)]) }}"
                                            width="{{ $photo->width }}"
                                            height="{{ $photo->height }}"
                                            class="js-lazy-img w-full h-auto object-cover"
                                            @else
                                            src="{{ route('thumbnails.show', ['gallery' => $photo->photo_gallery_id, 'photo' => basename($photo->path)]) }}"
                                            class="w-full h-auto object-cover"
                                            @endif
                                        alt="{{ $photo->alt ?? 'Photo #' . $photo->id }}"
                                        loading="lazy">
                                    <div class="js-lazy-spinner absolute inset-0 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-200">
                                        <div class="h-8 w-8 rounded-full border-2 border-gray-300 border-t-gray-600 animate-spin"></div>
                                    </div>
                                </div>
                                <label class="photo-select-checkbox absolute right-3 top-3 z-10 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full"
                                       onclick="event.stopPropagation()">
                                    <input type="checkbox" data-photo-checkbox value="{{ $photo->id }}"
                                        class="js-photo-checkbox h-5 w-5 cursor-pointer accent-white">
                                </label>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end">
                                    <div class="p-4 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                        <p class="text-white font-medium">{{ $photo->alt ?? 'Photo #' . $photo->id }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

        <!-- Slideshow Modal -->
        <div id="slideshow-modal"
            class="slideshow-modal fixed inset-0 bg-black/80 z-50 flex items-center justify-center">
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
                    <div id="slide-spinner" class="slide-spinner">
                        <div class="spinner-ring"></div>
                    </div>
                </div>
                <div class="text-white text-center mt-6">
                    <p id="slide-counter" class="text-sm font-medium tracking-wider"></p>
                    <p id="slide-alt" class="mt-2 text-white/80"></p>
                </div>
            </div>
        </div>
</x-layout>
