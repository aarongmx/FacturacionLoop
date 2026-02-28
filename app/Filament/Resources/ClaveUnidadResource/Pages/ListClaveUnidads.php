<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClaveUnidadResource\Pages;

use App\Filament\Resources\ClaveUnidadResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListClaveUnidads extends ListRecords
{
    #[Override]
    protected static string $resource = ClaveUnidadResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
