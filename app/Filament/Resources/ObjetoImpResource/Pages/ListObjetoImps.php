<?php

declare(strict_types=1);

namespace App\Filament\Resources\ObjetoImpResource\Pages;

use App\Filament\Resources\ObjetoImpResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListObjetoImps extends ListRecords
{
    #[Override]
    protected static string $resource = ObjetoImpResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
