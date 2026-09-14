<x-filament-panels::page>
    @vite('resources/js/univers-layout.js')

    <div class="space-y-6" x-data="universLayoutEditor()" x-bind:data-dirty="$wire.isDirty">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ __('admin.layout.page_heading') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.layout.page_description') }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-filament::button x-on:click.prevent="saveLayout" icon="heroicon-o-check">{{ __('admin.layout.save_layout') }}</x-filament::button>
                <x-filament::button wire:click="processAll" color="gray" icon="heroicon-o-arrow-path">{{ __('admin.layout.process_all') }}</x-filament::button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <x-filament::section>
                <x-slot name="heading">{{ __('admin.layout.heading') }}</x-slot>
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button
                        size="sm"
                        :color="$mode === 'preset' ? 'primary' : 'gray'"
                        wire:click="chooseMode('preset')"
                    >{{ __('admin.layout.presets') }}</x-filament::button>
                    <x-filament::button
                        size="sm"
                        :color="$mode === 'custom' ? 'primary' : 'gray'"
                        wire:click="chooseMode('custom')"
                    >{{ __('admin.layout.custom_grid') }}</x-filament::button>
                    <x-filament::button
                        size="sm"
                        :color="$mode === 'generic' ? 'primary' : 'gray'"
                        wire:click="chooseMode('generic')"
                    >{{ __('admin.layout.generic_masonry') }}</x-filament::button>
                </div>

                @if ($mode === 'preset')
                    <div class="mt-4 max-w-sm">
                        <label class="fi-fo-field-wrp-label block text-sm font-medium text-gray-950 dark:text-white">{{ __('admin.layout.preset') }}</label>
                        <select wire:change="applyPreset($event.target.value)" class="fi-select-input mt-2 block w-full rounded-lg border-gray-300 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-white">
                            <option value="">{{ __('admin.layout.choose_preset') }}</option>
                            @foreach ($this->presets() as $key => $label)
                                @php($presetCount = (int) str($key)->afterLast('-')->toString())
                                <option value="{{ $key }}" @selected($preset === $key) @disabled($presetCount !== $this->universItemsCount())>{{ $label }}{{ $presetCount !== $this->universItemsCount() ? __('admin.layout.images_suffix', ['count' => $presetCount]) : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($mode === 'generic')
                    <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">{{ __('admin.layout.generic_hint') }}</p>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.layout.custom_hint') }}</p>
                @endif

                <div class="mt-5 flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.layout.editor') }}</span>
                    <div class="flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-white/5">
                        <button type="button" class="rounded-md px-3 py-1 text-xs {{ $preview === 'desktop' ? 'bg-white shadow dark:bg-white/10' : '' }}" wire:click="setPreview('desktop')">{{ __('admin.layout.desktop') }}</button>
                        <button type="button" class="rounded-md px-3 py-1 text-xs {{ $preview === 'mobile' ? 'bg-white shadow dark:bg-white/10' : '' }}" wire:click="setPreview('mobile')">{{ __('admin.layout.mobile') }}</button>
                    </div>
                </div>

                <div wire:ignore class="{{ $preview === 'mobile' ? 'mx-auto max-w-sm' : '' }} univers-editor-grid--{{ $mode }} mt-3 rounded-xl border border-dashed border-gray-300 p-3 dark:border-white/10">
                    <script type="application/json" data-univers-editor-state>{!! json_encode(['items' => $this->editorItems(), 'mode' => $mode, 'preview' => $preview, 'preset' => $preset], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                    <div id="univers-layout-grid" class="univers-layout-grid univers-layout-grid--{{ $mode }}" data-mode="{{ $mode }}" data-preview="{{ $preview }}" wire:key="univers-layout-grid"></div>
                </div>

                <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-white/10" x-data="{ focal: { id: @js($focalPointUniversId), x: @js($focalX), y: @js($focalY), source: @js($this->focalPreviewSource) } }">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-medium text-gray-950 dark:text-white">{{ __('admin.layout.focal_point') }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.layout.focal_point_hint') }}</p>
                        </div>
                        <select
                            class="fi-select-input rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5 dark:text-white"
                            x-on:change="focal.id === '' ? (focal.id = null, focal.source = null) : $wire.selectFocalPoint(Number(focal.id)).then(() => { focal.x = $wire.focalX; focal.y = $wire.focalY; focal.source = $wire.focalPreviewSource })"
                        >
                            <option value="">{{ __('admin.layout.choose_image') }}</option>
                            @foreach ($this->universItems as $univers)
                                <option value="{{ $univers->id }}" @selected($focalPointUniversId === $univers->id)>{{ $univers->title ?: __('admin.univers.image_number', ['id' => $univers->id]) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <template x-if="focal.id">
                        <div>
                            <div
                                class="relative mt-4 aspect-[16/9] overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800"
                                x-on:click="$event.offsetX && $event.offsetY && (focal.x = $event.offsetX / $event.target.clientWidth, focal.y = $event.offsetY / $event.target.clientHeight, $wire.setFocalX(focal.x), $wire.setFocalY(focal.y))"
                            >
                                <img
                                    x-show="focal.source"
                                    :src="focal.source"
                                    alt="{{ __('admin.layout.focal_preview_alt') }}"
                                    class="absolute inset-0 block h-full w-full object-cover"
                                    :style="`object-position: ${focal.x * 100}% ${focal.y * 100}%`"
                                >
                                <span
                                    class="pointer-events-none absolute h-5 w-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow"
                                    :style="`left: ${focal.x * 100}%; top: ${focal.y * 100}%`"
                                ></span>
                            </div>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <label class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('admin.layout.horizontal_position') }}
                                    <input type="range" min="0" max="1" step="0.01" x-model="focal.x" x-on:input="$wire.setFocalX(focal.x)" class="mt-2 w-full">
                                </label>
                                <label class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('admin.layout.vertical_position') }}
                                    <input type="range" min="0" max="1" step="0.01" x-model="focal.y" x-on:input="$wire.setFocalY(focal.y)" class="mt-2 w-full">
                                </label>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <x-filament::button size="sm" wire:click="saveFocalPoint">{{ __('admin.layout.save_focal_point') }}</x-filament::button>
                            </div>
                        </div>
                    </template>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('admin.layout.image_status') }}</x-slot>
                <div class="space-y-3">
                    @foreach ($this->universItems as $univers)
                        <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-2 dark:border-white/10">
                            <img src="{{ URL::temporarySignedRoute('univers.source', now()->addMinutes(10), $univers) }}" alt="" class="block h-12 w-12 shrink-0 rounded object-cover">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium">{{ $univers->title ?: __('admin.univers.untitled_image') }}</div>
                                <div class="text-xs text-gray-500">{{ str_replace('_', ' ', $univers->processing_status) }}</div>
                            </div>
                            @if (in_array($univers->processing_status, ['unprocessed', 'failed', 'partially_processed'], true))
                                <x-filament::icon-button icon="heroicon-o-arrow-path" wire:click="process({{ $univers->id }})" label="{{ __('admin.layout.process_image') }}" />
                            @else
                                <x-filament::icon-button icon="heroicon-o-arrow-path" wire:click="reprocess({{ $univers->id }})" label="{{ __('admin.layout.reprocess_image') }}" />
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    </div>

</x-filament-panels::page>
