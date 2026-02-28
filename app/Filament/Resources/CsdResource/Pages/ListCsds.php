<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsdResource\Pages;

use App\Filament\Resources\CsdResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCsds extends ListRecords
{
    protected static string $resource = CsdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Subir CSD'),
        ];
    }
}
