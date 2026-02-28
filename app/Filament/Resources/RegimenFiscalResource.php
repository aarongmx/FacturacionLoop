<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RegimenFiscalResource\Pages\ListRegimenFiscals;
use App\Models\RegimenFiscal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use UnitEnum;

final class RegimenFiscalResource extends Resource
{
    #[Override]
    protected static ?string $model = RegimenFiscal::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Catálogos SAT';

    #[Override]
    protected static ?string $modelLabel = 'Régimen Fiscal';

    #[Override]
    protected static ?string $pluralModelLabel = 'Regímenes Fiscales';

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
            'index' => ListRegimenFiscals::route('/'),
        ];
    }
}
