<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FormaPagoResource\Pages;
use App\Models\FormaPago;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class FormaPagoResource extends Resource
{
    protected static ?string $model = FormaPago::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos SAT';

    protected static ?string $modelLabel = 'Forma de Pago';

    protected static ?string $pluralModelLabel = 'Formas de Pago';

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
                IconColumn::make('bancarizado')
                    ->boolean()
                    ->label('Bancarizado'),
            ])
            ->defaultSort('clave')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormaPagos::route('/'),
        ];
    }
}
