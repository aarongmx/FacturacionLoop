<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ClaveProdServResource\Pages;
use App\Models\ClaveProdServ;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class ClaveProdServResource extends Resource
{
    protected static ?string $model = ClaveProdServ::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos SAT';

    protected static ?string $modelLabel = 'Clave Producto/Servicio';

    protected static ?string $pluralModelLabel = 'Claves de Producto/Servicio';

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
                TextColumn::make('descripcion')
                    ->searchable()
                    ->label('Descripción')
                    ->wrap()
                    ->limit(100),
                TextColumn::make('incluye_iva')
                    ->label('Incluye IVA'),
                TextColumn::make('incluye_ieps')
                    ->label('Incluye IEPS'),
            ])
            ->defaultSort('clave')
            ->paginated([25, 50, 100])
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClaveProdServs::route('/'),
        ];
    }
}
