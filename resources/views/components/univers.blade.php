<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    @foreach ($univers as $item)
        <div class="card bg-white shadow-lg rounded-lg overflow-hidden">
        <img src="{{ Storage::disk('public')->url($item->path) }}" alt="{{ $item->title }}" class="w-full h-48 object-cover">
        @if (isset($item->title) || isset($item->description))
            <div class="p-4">
            @if (isset($item->title))
                <h3 class="text-xl font-semibold text-base-200">{{ $item->title }}</h3>
            @endif
            @if (isset($item->description))
                <p class="text-gray-600 mt-2">{{ $item->description }}</p>
            @endif
            </div>
        </div>
        @endif
    @endforeach
</div>
