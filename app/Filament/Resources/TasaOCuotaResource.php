<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TasaOCuotaResource\Pages;
use App\Models\TasaOCuota;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class TasaOCuotaResource extends Resource
{
    protected static ?string $model = TasaOCuota::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos SAT';

    protected static ?string $modelLabel = 'Tasa o Cuota';

    protected static ?string $pluralModelLabel = 'Tasas o Cuotas';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('impuesto')
                    ->searchable()
                    ->sortable()
                    ->label('Impuesto'),
                TextColumn::make('factor')
                    ->searchable()
                    ->sortable()
                    ->label('Factor'),
                TextColumn::make('valor_minimo')
                    ->label('Valor Mínimo'),
                TextColumn::make('valor_maximo')
                    ->label('Valor Máximo'),
                IconColumn::make('traslado')
                    ->boolean()
                    ->label('Traslado'),
                IconColumn::make('retencion')
                    ->boolean()
                    ->label('Retención'),
            ])
            ->defaultSort('impuesto')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasaOCuotas::route('/'),
        ];
    }
}
