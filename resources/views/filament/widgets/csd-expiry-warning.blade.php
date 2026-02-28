<x-filament-widgets::widget>
    @if($this->expiringCsd)
        <x-filament::section>
            <div class="flex items-center gap-x-3 rounded-lg bg-warning-50 p-4 ring-1 ring-warning-200 dark:bg-warning-950 dark:ring-warning-800">
                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-warning-500" />
                <div class="flex-1">
                    <p class="text-sm font-medium text-warning-800 dark:text-warning-200">
                        El certificado de sello digital
                        <span class="font-bold">{{ $this->expiringCsd->no_certificado }}</span>
                        vence en
                        <span class="font-bold">{{ $this->daysRemaining }}</span>
                        {{ $this->daysRemaining === 1 ? 'día' : 'días' }}.
                    </p>
                    <p class="mt-1 text-xs text-warning-600 dark:text-warning-400">
                        Fecha de vencimiento: {{ $this->expiringCsd->fecha_fin->format('d/m/Y') }}.
                        Suba un nuevo CSD antes de que expire para evitar interrupciones en el timbrado.
                    </p>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
