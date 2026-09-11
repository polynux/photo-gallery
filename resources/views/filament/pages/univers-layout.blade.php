<x-filament-panels::page>
    @vite('resources/js/univers-layout.js')

    <div class="space-y-6" x-data="universLayoutEditor(@js($this->editorItems()), @js($mode), @js($preview), @js($preset))" x-bind:data-dirty="$wire.isDirty">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Homepage Univers</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Arrange the section here and preview the result without opening the homepage.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-filament::button x-on:click.prevent="saveLayout" icon="heroicon-o-check">Save layout</x-filament::button>
                <x-filament::button wire:click="processAll" color="gray" icon="heroicon-o-arrow-path">Process all</x-filament::button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <x-filament::section>
                <x-slot name="heading">Layout</x-slot>
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button
                        size="sm"
                        :color="$mode === 'preset' ? 'primary' : 'gray'"
                        wire:click="chooseMode('preset')"
                    >Presets</x-filament::button>
                    <x-filament::button
                        size="sm"
                        :color="$mode === 'custom' ? 'primary' : 'gray'"
                        wire:click="chooseMode('custom')"
                    >Custom 12-column grid</x-filament::button>
                    <x-filament::button
                        size="sm"
                        :color="$mode === 'generic' ? 'primary' : 'gray'"
                        wire:click="chooseMode('generic')"
                    >Generic masonry</x-filament::button>
                </div>

                @if ($mode === 'preset')
                    <div class="mt-4 max-w-sm">
                        <label class="fi-fo-field-wrp-label block text-sm font-medium text-gray-950 dark:text-white">Preset</label>
                        <select wire:change="applyPreset($event.target.value)" class="fi-select-input mt-2 block w-full rounded-lg border-gray-300 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                            <option value="">Choose a preset</option>
                            @foreach ($this->presets() as $key => $label)
                                @php($presetCount = (int) str($key)->afterLast('-')->toString())
                                <option value="{{ $key }}" @selected($preset === $key) @disabled($presetCount !== $this->universItemsCount())>{{ $label }}{{ $presetCount !== $this->universItemsCount() ? " ({$presetCount} images)" : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($mode === 'generic')
                    <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">Reorder the images without positioning them on a canvas.</p>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Drag and resize tiles. The grid has 12 columns and grows vertically as needed.</p>
                @endif

                <div class="mt-5 flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Editor</span>
                    <div class="flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
                        <button type="button" class="rounded-md px-3 py-1 text-xs {{ $preview === 'desktop' ? 'bg-white shadow dark:bg-white/10' : '' }}" wire:click="setPreview('desktop')">Desktop</button>
                        <button type="button" class="rounded-md px-3 py-1 text-xs {{ $preview === 'mobile' ? 'bg-white shadow dark:bg-white/10' : '' }}" wire:click="setPreview('mobile')">Mobile</button>
                    </div>
                </div>

                <div wire:ignore class="{{ $preview === 'mobile' ? 'mx-auto max-w-sm' : '' }} univers-editor-grid--{{ $mode }} mt-3 rounded-xl border border-dashed border-gray-300 p-3 dark:border-white/10" :class="preview === 'mobile' ? 'mx-auto max-w-sm' : ''">
                    <div id="univers-layout-grid" class="univers-layout-grid univers-layout-grid--{{ $mode }}" data-mode="{{ $mode }}" data-preview="{{ $preview }}" wire:key="univers-layout-grid"></div>
                </div>

                <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-white/10">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-medium text-gray-950 dark:text-white">Focal point</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Set the part of the image that should remain visible when CSS crops the tile.</p>
                        </div>
                        <select wire:change="selectFocalPoint($event.target.value)" class="fi-select-input rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                            <option value="">Choose an image</option>
                            @foreach ($this->universItems as $univers)
                                <option value="{{ $univers->id }}" @selected($focalPointUniversId === $univers->id)>{{ $univers->title ?: "Image {$univers->id}" }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($focalPointUniversId)
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="text-sm text-gray-700 dark:text-gray-300">
                                Horizontal position
                                <input type="range" min="0" max="1" step="0.01" wire:model.live="focalX" class="mt-2 w-full">
                            </label>
                            <label class="text-sm text-gray-700 dark:text-gray-300">
                                Vertical position
                                <input type="range" min="0" max="1" step="0.01" wire:model.live="focalY" class="mt-2 w-full">
                            </label>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <x-filament::button size="sm" wire:click="saveFocalPoint">Save focal point</x-filament::button>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Image status</x-slot>
                <div class="space-y-3">
                    @foreach ($this->universItems as $univers)
                        <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-2 dark:border-white/10">
                            <img src="{{ URL::temporarySignedRoute('univers.source', now()->addMinutes(10), $univers) }}" alt="" class="block h-12 w-12 shrink-0 rounded object-cover">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium">{{ $univers->title ?: 'Untitled image' }}</div>
                                <div class="text-xs text-gray-500">{{ str_replace('_', ' ', $univers->processing_status) }}</div>
                            </div>
                            @if (in_array($univers->processing_status, ['unprocessed', 'failed', 'partially_processed'], true))
                                <x-filament::icon-button icon="heroicon-o-arrow-path" wire:click="process({{ $univers->id }})" label="Process image" />
                            @else
                                <x-filament::icon-button icon="heroicon-o-arrow-path" wire:click="reprocess({{ $univers->id }})" label="Reprocess image" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    </div>

</x-filament-panels::page>
