<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReceptorResource\Pages;

use App\Filament\Resources\ReceptorResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

final class CreateReceptor extends CreateRecord
{
    #[Override]
    protected static string $resource = ReceptorResource::class;
}
