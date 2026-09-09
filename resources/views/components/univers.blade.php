@if ($univers->isNotEmpty())
    <div class="univers-grid univers-grid--{{ $layout['mode'] }} max-w-7xl mx-auto p-2" data-univers-layout="{{ $layout['mode'] }}">
        @if ($layout['notice'])
            <p class="univers-grid__notice col-span-full mb-3 text-sm text-gray-500">{{ $layout['notice'] }}</p>
        @endif

        @foreach ($layout['items'] as $item)
            @php
                $universItem = $univers->firstWhere('id', $item['univers_id']);
                $sourcePath = $universItem->source_path;
                $imageService = app(\App\Services\UniversImageService::class);
                $imageUrl = $imageService->url($universItem, 800) ?? route('univers.source', $universItem);
                $imageJpegUrl = $imageService->url($universItem, 800, 'jpg') ?? $imageUrl;
                $image500Url = $imageService->url($universItem, 500, 'jpg') ?? $imageJpegUrl;
                $image300Url = $imageService->url($universItem, 300, 'jpg') ?? $image500Url;
            @endphp
            <div
                class="univers-tile group relative overflow-hidden rounded-lg bg-white shadow-lg {{ $item['class'] }}"
                style="--univers-x: {{ $item['x'] }}; --univers-y: {{ $item['y'] }}; --univers-width: {{ $item['width'] }}; --univers-height: {{ $item['height'] }};"
            >
                <picture>
                    @if ($imageService->url($universItem, 300, 'webp') || $imageService->url($universItem, 500, 'webp') || $imageService->url($universItem, 800, 'webp'))
                        <source
                            type="image/webp"
                            srcset="{{ $imageService->url($universItem, 300, 'webp') ?? $image300Url }} 300w, {{ $imageService->url($universItem, 500, 'webp') ?? $image500Url }} 500w, {{ $imageService->url($universItem, 800, 'webp') ?? $imageUrl }} 800w"
                            sizes="(max-width: 640px) 100vw, 800px"
                        >
                    @endif
                    <img
                        src="{{ $imageJpegUrl }}"
                        srcset="{{ $image300Url }} 300w, {{ $image500Url }} 500w, {{ $imageJpegUrl }} 800w"
                        sizes="(max-width: 640px) 100vw, 800px"
                        alt="{{ $universItem->title }}"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        style="object-position: {{ $universItem->focal_x * 100 }}% {{ $universItem->focal_y * 100 }}%;"
                        loading="lazy"
                    >
                </picture>
                @if ($universItem->title || $universItem->description)
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/50 to-transparent p-4 text-white">
                        @if ($universItem->title)
                            <h3 class="text-xl font-semibold">{{ $universItem->title }}</h3>
                        @endif
                        @if ($universItem->description)
                            <p class="mt-2 text-sm text-white/80">{{ $universItem->description }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    <style>
        .univers-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            grid-auto-rows: minmax(32px, 6vw);
            grid-auto-flow: dense;
            gap: 10px;
        }

        .univers-tile {
            grid-column: span var(--univers-width);
            grid-row: span var(--univers-height);
            min-height: 0;
        }

        .univers-grid--custom .univers-tile {
                    grid-column: calc(var(--univers-x) + 1) / span var(--univers-width);
                    grid-row: calc(var(--univers-y) + 1) / span var(--univers-height);
        }

        .univers-grid__notice {
            grid-column: 1 / -1;
        }

        @media (max-width: 640px) {
            .univers-grid {
                grid-template-columns: 1fr;
                grid-auto-rows: minmax(180px, 55vw);
            }

            .univers-tile,
            .univers-grid--custom .univers-tile {
                grid-column: 1 / -1;
                grid-row: span 1;
            }
        }
    </style>
@endif
