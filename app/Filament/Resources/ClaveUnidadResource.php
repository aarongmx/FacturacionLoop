<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ClaveUnidadResource\Pages;
use App\Models\ClaveUnidad;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class ClaveUnidadResource extends Resource
{
    protected static ?string $model = ClaveUnidad::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos SAT';

    protected static ?string $modelLabel = 'Clave de Unidad';

    protected static ?string $pluralModelLabel = 'Claves de Unidad';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clave')
                    ->searchable()
                    ->sortable()
                    ->label('Clave'),
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap()
                    ->limit(100),
                TextColumn::make('simbolo')
                    ->label('Símbolo'),
            ])
            ->defaultSort('clave')
            ->paginated([25, 50, 100])
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClaveUnidads::route('/'),
        ];
    }
}
