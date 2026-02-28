<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TipoRelacionResource\Pages\ListTipoRelacions;
use App\Models\TipoRelacion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use UnitEnum;

final class TipoRelacionResource extends Resource
{
    #[Override]
    protected static ?string $model = TipoRelacion::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Catálogos SAT';

    #[Override]
    protected static ?string $modelLabel = 'Tipo de Relación';

    #[Override]
    protected static ?string $pluralModelLabel = 'Tipos de Relación';

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
            'index' => ListTipoRelacions::route('/'),
        ];
    }
}
