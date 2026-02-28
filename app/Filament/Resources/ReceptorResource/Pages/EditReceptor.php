<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReceptorResource\Pages;

use App\Filament\Resources\ReceptorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditReceptor extends EditRecord
{
    #[Override]
    protected static string $resource = ReceptorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Archivar'),
        ];
    }
}
