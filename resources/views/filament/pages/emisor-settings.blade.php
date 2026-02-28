<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Datos Fiscales del Emisor</x-slot>

        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button wire:click="save" icon="heroicon-o-check">
                Guardar
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
