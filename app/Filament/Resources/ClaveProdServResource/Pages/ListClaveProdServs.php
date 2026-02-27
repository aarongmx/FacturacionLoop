<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClaveProdServResource\Pages;

use App\Filament\Resources\ClaveProdServResource;
use Filament\Resources\Pages\ListRecords;

final class ListClaveProdServs extends ListRecords
{
    protected static string $resource = ClaveProdServResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
