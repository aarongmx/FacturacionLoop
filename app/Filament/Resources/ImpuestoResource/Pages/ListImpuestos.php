<?php

declare(strict_types=1);

namespace App\Filament\Resources\ImpuestoResource\Pages;

use App\Filament\Resources\ImpuestoResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListImpuestos extends ListRecords
{
    #[Override]
    protected static string $resource = ImpuestoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
