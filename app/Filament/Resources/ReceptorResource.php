<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ReceptorResource\Pages\CreateReceptor;
use App\Filament\Resources\ReceptorResource\Pages\EditReceptor;
use App\Filament\Resources\ReceptorResource\Pages\ListReceptores;
use App\Models\Receptor;
use App\Models\RegimenFiscal;
use App\Models\UsoCfdi;
use App\Rules\ValidaRfc;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;
use UnitEnum;

final class ReceptorResource extends Resource
{
    #[Override]
    protected static ?string $model = Receptor::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Entidades';

    #[Override]
    protected static ?string $modelLabel = 'Receptor';

    #[Override]
    protected static ?string $pluralModelLabel = 'Receptores';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('rfc')
                    ->label('RFC')
                    ->required()
                    ->maxLength(13)
                    ->rules([new ValidaRfc])
                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? mb_strtoupper(mb_trim($state)) : null)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if ($state === null) {
                            return;
                        }

                        $rfc = mb_strtoupper(mb_trim($state));

                        // Auto-fill for público en general per locked decision
                        if ($rfc === 'XAXX010101000') {
                            $set('nombre_fiscal', 'PUBLICO EN GENERAL');
                            $set('regimen_fiscal_clave', '616');
                            $set('uso_cfdi_clave', 'S01');
                        }
                    }),
                TextInput::make('nombre_fiscal')
                    ->label('Nombre Fiscal / Razón Social')
                    ->required()
                    ->maxLength(300),
                TextInput::make('domicilio_fiscal_cp')
                    ->label('Código Postal del Domicilio Fiscal')
                    ->required()
                    ->maxLength(5)
                    ->minLength(5)
                    ->numeric(),
                Select::make('regimen_fiscal_clave')
                    ->label('Régimen Fiscal')
                    ->options(RegimenFiscal::query()->pluck('descripcion', 'clave'))
                    ->searchable()
                    ->nullable(),
                Select::make('uso_cfdi_clave')
                    ->label('Uso CFDI Predeterminado')
                    ->options(UsoCfdi::query()->pluck('descripcion', 'clave'))
                    ->searchable()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rfc')
                    ->label('RFC')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre_fiscal')
                    ->label('Nombre Fiscal')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('domicilio_fiscal_cp')
                    ->label('CP')
                    ->sortable(),
                TextColumn::make('regimenFiscal.descripcion')
                    ->label('Régimen Fiscal')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('usoCfdi.descripcion')
                    ->label('Uso CFDI')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->label('Archivar'),
                RestoreAction::make(),
                ForceDeleteAction::make()->label('Eliminar permanentemente'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->defaultSort('nombre_fiscal')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReceptores::route('/'),
            'create' => CreateReceptor::route('/create'),
            'edit' => EditReceptor::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
