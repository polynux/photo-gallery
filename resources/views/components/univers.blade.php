@if ($univers->isNotEmpty())
    <div class="grid-wrapper max-w-7xl mx-auto p-2">
        @foreach ($univers as $item)
            @php
                $class = $classes[$loop->index % count($classes)];
            @endphp
            <div class="group relative bg-white shadow-lg rounded-lg overflow-hidden {{ $class }}">
                <img src="{{ Storage::disk('public')->url($item->path) }}" alt="{{ $item->title }}" class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-105">
                @if ($item->title || $item->description)
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/50 to-transparent p-4 text-white">
                        @if ($item->title)
                            <h3 class="text-xl font-semibold">{{ $item->title }}</h3>
                        @endif
                        @if ($item->description)
                            <p class="mt-2 text-sm text-white/80">{{ $item->description }}</p>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    <style>
    /* Reset CSS */
    .grid-wrapper * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    .grid-wrapper img {
        max-width: 100%;
        height: auto;
        vertical-align: middle;
        display: inline-block;
    }

    /* Main CSS */
    .grid-wrapper > div {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .grid-wrapper > div > img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 5px;
    }

    .grid-wrapper {
        display: grid;
        grid-gap: 10px;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        grid-auto-rows: 200px;
        grid-auto-flow: dense;
    }
    .grid-wrapper .wide {
        grid-column: span 2;
    }
    .grid-wrapper .tall {
        grid-row: span 2;
    }
    .grid-wrapper .big {
        grid-column: span 2;
        grid-row: span 2;
    }

    @media (max-width: 600px) {
      .grid-wrapper {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (min-width: 601px) and (max-width: 900px) {
      .grid-wrapper {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (min-width: 901px) {
      .grid-wrapper {
        grid-template-columns: repeat(4, 1fr);
      }
    }
    </style>
@endif
