<?php

declare(strict_types=1);

namespace App\Filament\Resources\TipoDeComprobanteResource\Pages;

use App\Filament\Resources\TipoDeComprobanteResource;
use Filament\Resources\Pages\ListRecords;

final class ListTipoDeComprobantes extends ListRecords
{
    protected static string $resource = TipoDeComprobanteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
