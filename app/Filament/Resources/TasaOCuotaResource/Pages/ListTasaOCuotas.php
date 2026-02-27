<?php

declare(strict_types=1);

namespace App\Filament\Resources\TasaOCuotaResource\Pages;

use App\Filament\Resources\TasaOCuotaResource;
use Filament\Resources\Pages\ListRecords;

final class ListTasaOCuotas extends ListRecords
{
    protected static string $resource = TasaOCuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
