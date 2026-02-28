<?php

declare(strict_types=1);

namespace App\Filament\Resources\RegimenFiscalResource\Pages;

use App\Filament\Resources\RegimenFiscalResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListRegimenFiscals extends ListRecords
{
    #[Override]
    protected static string $resource = RegimenFiscalResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
