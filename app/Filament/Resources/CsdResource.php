<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Actions\ActivateCsdAction;
use App\Actions\DeactivateCsdAction;
use App\Enums\CsdStatus;
use App\Filament\Resources\CsdResource\Pages\CreateCsd;
use App\Filament\Resources\CsdResource\Pages\ListCsds;
use App\Filament\Resources\CsdResource\Pages\ViewCsd;
use App\Models\Csd;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use UnitEnum;

final class CsdResource extends Resource
{
    #[Override]
    protected static ?string $model = Csd::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    #[Override]
    protected static ?string $modelLabel = 'Certificado de Sello Digital';

    #[Override]
    protected static ?string $pluralModelLabel = 'Certificados de Sello Digital';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_certificado')
                    ->label('No. Certificado')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rfc')
                    ->label('RFC')
                    ->searchable(),
                TextColumn::make('fecha_inicio')
                    ->label('Vigencia desde')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fecha_fin')
                    ->label('Vigencia hasta')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Fecha de carga')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('activar')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('¿Activar este CSD?')
                    ->modalDescription('Se desactivará el CSD activo actual y este certificado será usado para firmar facturas.')
                    ->action(fn (Csd $record) => resolve(ActivateCsdAction::class)($record))
                    ->visible(fn (Csd $record): bool => $record->status !== CsdStatus::Active && ! $record->fecha_fin->isPast()),
                Action::make('desactivar')
                    ->label('Desactivar')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('¿Desactivar este CSD?')
                    ->modalDescription('No podrás timbrar facturas hasta que actives otro certificado.')
                    ->action(fn (Csd $record) => resolve(DeactivateCsdAction::class)($record))
                    ->visible(fn (Csd $record): bool => $record->status === CsdStatus::Active),
                DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCsds::route('/'),
            'create' => CreateCsd::route('/create'),
            'view' => ViewCsd::route('/{record}'),
        ];
    }
}
