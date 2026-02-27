<?php

declare(strict_types=1);

namespace App\Filament\Resources\TipoRelacionResource\Pages;

use App\Filament\Resources\TipoRelacionResource;
use Filament\Resources\Pages\ListRecords;

final class ListTipoRelacions extends ListRecords
{
    protected static string $resource = TipoRelacionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
