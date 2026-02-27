<?php

declare(strict_types=1);

namespace App\Filament\Resources\UsoCfdiResource\Pages;

use App\Filament\Resources\UsoCfdiResource;
use Filament\Resources\Pages\ListRecords;

final class ListUsoCfdis extends ListRecords
{
    protected static string $resource = UsoCfdiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
