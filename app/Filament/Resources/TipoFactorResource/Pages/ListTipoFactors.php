<?php

declare(strict_types=1);

namespace App\Filament\Resources\TipoFactorResource\Pages;

use App\Filament\Resources\TipoFactorResource;
use Filament\Resources\Pages\ListRecords;

final class ListTipoFactors extends ListRecords
{
    protected static string $resource = TipoFactorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
