<?php

declare(strict_types=1);

namespace App\Filament\Resources\MetodoPagoResource\Pages;

use App\Filament\Resources\MetodoPagoResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListMetodoPagos extends ListRecords
{
    #[Override]
    protected static string $resource = MetodoPagoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
