@if ($univers->isNotEmpty())
    <div class="univers-grid univers-grid--{{ $layout['mode'] }} mx-auto max-w-7xl p-2" data-univers-layout="{{ $layout['mode'] }}">
        @if ($layout['notice'])
            <p class="univers-grid__notice col-span-full mb-3 text-sm text-gray-500">{{ $layout['notice'] }}</p>
        @endif

        @foreach ($layout['items'] as $item)
            @php($universItem = $univers->firstWhere('id', $item['univers_id']))
            @continue(! $universItem)
            @php($sources = $universItem->gallerySources)
            <div
                class="univers-tile group relative overflow-hidden rounded-lg bg-white shadow-lg {{ $item['class'] }}"
                style="--univers-x: {{ $item['x'] }}; --univers-y: {{ $item['y'] }}; --univers-width: {{ $item['width'] }}; --univers-height: {{ $item['height'] }};"
            >
                <picture>
                    @if ($sources['webp'])
                        <source
                            type="image/webp"
                            srcset="{{ collect($sources['widths'])->map(fn (array $formats, string $width): string => ($formats['webp'] ?? $formats['jpg'])." {$width}w")->implode(', ') }}"
                            sizes="(max-width: 640px) 100vw, 800px"
                        >
                    @endif
                    <img
                        src="{{ $sources['jpeg'] }}"
                        srcset="{{ collect($sources['widths'])->map(fn (array $formats, string $width): string => "{$formats['jpg']} {$width}w")->implode(', ') }}"
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
@endif