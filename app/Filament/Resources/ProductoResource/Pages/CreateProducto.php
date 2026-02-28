<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

final class CreateProducto extends CreateRecord
{
    #[Override]
    protected static string $resource = ProductoResource::class;
}
