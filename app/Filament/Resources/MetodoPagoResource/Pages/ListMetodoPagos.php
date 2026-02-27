<?php

declare(strict_types=1);

namespace App\Filament\Resources\MetodoPagoResource\Pages;

use App\Filament\Resources\MetodoPagoResource;
use Filament\Resources\Pages\ListRecords;

final class ListMetodoPagos extends ListRecords
{
    protected static string $resource = MetodoPagoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
