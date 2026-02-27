<?php

declare(strict_types=1);

namespace App\Filament\Resources\FormaPagoResource\Pages;

use App\Filament\Resources\FormaPagoResource;
use Filament\Resources\Pages\ListRecords;

final class ListFormaPagos extends ListRecords
{
    protected static string $resource = FormaPagoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
