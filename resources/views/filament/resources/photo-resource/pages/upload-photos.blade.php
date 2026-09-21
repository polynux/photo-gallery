<x-filament-panels::page>
    <form wire:submit="submit" class="fi-form space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            {{ __('admin.photo.upload_photos') }}
        </x-filament::button>
    </form>

</x-filament-panels::page>