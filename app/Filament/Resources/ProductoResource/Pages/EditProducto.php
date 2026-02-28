<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditProducto extends EditRecord
{
    #[Override]
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Archivar'),
        ];
    }
}
