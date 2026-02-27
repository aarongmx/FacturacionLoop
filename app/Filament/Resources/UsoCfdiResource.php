<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UsoCfdiResource\Pages;
use App\Models\UsoCfdi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class UsoCfdiResource extends Resource
{
    protected static ?string $model = UsoCfdi::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos SAT';

    protected static ?string $modelLabel = 'Uso CFDI';

    protected static ?string $pluralModelLabel = 'Usos CFDI';

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
                    ->wrap(),
                IconColumn::make('aplica_fisica')
                    ->boolean()
                    ->label('Persona Física'),
                IconColumn::make('aplica_moral')
                    ->boolean()
                    ->label('Persona Moral'),
            ])
            ->defaultSort('clave')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsoCfdis::route('/'),
        ];
    }
}
