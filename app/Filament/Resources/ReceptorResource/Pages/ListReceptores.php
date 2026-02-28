<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReceptorResource\Pages;

use App\Filament\Resources\ReceptorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListReceptores extends ListRecords
{
    #[Override]
    protected static string $resource = ReceptorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
