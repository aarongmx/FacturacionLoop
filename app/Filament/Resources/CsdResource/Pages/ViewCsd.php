<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsdResource\Pages;

use App\Filament\Resources\CsdResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

final class ViewCsd extends ViewRecord
{
    protected static string $resource = CsdResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('no_certificado')
                ->label('No. Certificado'),
            TextEntry::make('rfc')
                ->label('RFC'),
            TextEntry::make('fecha_inicio')
                ->label('Vigencia desde')
                ->date('d/m/Y'),
            TextEntry::make('fecha_fin')
                ->label('Vigencia hasta')
                ->date('d/m/Y'),
            TextEntry::make('status')
                ->label('Estado')
                ->badge(),
            TextEntry::make('created_at')
                ->label('Fecha de carga')
                ->dateTime('d/m/Y H:i'),
        ]);
    }
}
