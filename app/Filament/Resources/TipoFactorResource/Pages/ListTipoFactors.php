<?php

declare(strict_types=1);

namespace App\Filament\Resources\TipoFactorResource\Pages;

use App\Filament\Resources\TipoFactorResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListTipoFactors extends ListRecords
{
    #[Override]
    protected static string $resource = TipoFactorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
