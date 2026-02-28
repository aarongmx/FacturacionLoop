<?php

declare(strict_types=1);

namespace App\Filament\Resources\TipoDeComprobanteResource\Pages;

use App\Filament\Resources\TipoDeComprobanteResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListTipoDeComprobantes extends ListRecords
{
    #[Override]
    protected static string $resource = TipoDeComprobanteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
