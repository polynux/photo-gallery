<x-filament-panels::page>
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Upload Photos
        </x-filament::button>
    </x-filament-panels::form>

</x-filament-panels::page>
