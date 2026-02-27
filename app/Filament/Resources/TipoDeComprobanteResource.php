<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TipoDeComprobanteResource\Pages;
use App\Models\TipoDeComprobante;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class TipoDeComprobanteResource extends Resource
{
    protected static ?string $model = TipoDeComprobante::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos SAT';

    protected static ?string $modelLabel = 'Tipo de Comprobante';

    protected static ?string $pluralModelLabel = 'Tipos de Comprobante';

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
                    ->label('Descripción'),
            ])
            ->defaultSort('clave')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTipoDeComprobantes::route('/'),
        ];
    }
}
