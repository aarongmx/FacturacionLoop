<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages\CreateProducto;
use App\Filament\Resources\ProductoResource\Pages\EditProducto;
use App\Filament\Resources\ProductoResource\Pages\ListProductos;
use App\Models\ClaveProdServ;
use App\Models\ClaveUnidad;
use App\Models\Impuesto;
use App\Models\ObjetoImp;
use App\Models\Producto;
use App\Models\TasaOCuota;
use BackedEnum;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
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

final class ProductoResource extends Resource
{
    #[Override]
    protected static ?string $model = Producto::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Entidades';

    #[Override]
    protected static ?string $modelLabel = 'Producto / Servicio';

    #[Override]
    protected static ?string $pluralModelLabel = 'Productos / Servicios';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Datos del Producto')
                    ->schema([
                        Select::make('clave_prod_serv')
                            ->label('Clave Producto/Servicio SAT')
                            ->searchable()
                            ->getSearchResultsUsing(
                                fn (string $search): array => ClaveProdServ::query()
                                    ->where('clave', 'like', "%{$search}%")
                                    ->orWhere('descripcion', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('descripcion', 'clave')
                                    ->mapWithKeys(fn (string $desc, string $clave): array => [$clave => "{$clave} — {$desc}"])
                                    ->all()
                            )
                            ->getOptionLabelUsing(function (?string $value): ?string {
                                if ($value === null) {
                                    return null;
                                }

                                $descripcion = ClaveProdServ::query()
                                    ->where('clave', $value)
                                    ->value('descripcion');

                                return $descripcion !== null ? "{$value} — {$descripcion}" : $value;
                            })
                            ->required(),
                        Select::make('clave_unidad')
                            ->label('Clave Unidad SAT')
                            ->searchable()
                            ->getSearchResultsUsing(
                                fn (string $search): array => ClaveUnidad::query()
                                    ->where('clave', 'like', "%{$search}%")
                                    ->orWhere('nombre', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('nombre', 'clave')
                                    ->mapWithKeys(fn (string $nombre, string $clave): array => [$clave => "{$clave} — {$nombre}"])
                                    ->all()
                            )
                            ->getOptionLabelUsing(function (?string $value): ?string {
                                if ($value === null) {
                                    return null;
                                }

                                $nombre = ClaveUnidad::query()
                                    ->where('clave', $value)
                                    ->value('nombre');

                                return $nombre !== null ? "{$value} — {$nombre}" : $value;
                            })
                            ->required(),
                        TextInput::make('descripcion')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(1000),
                        TextInput::make('precio_unitario')
                            ->label('Precio Unitario')
                            ->required()
                            ->numeric()
                            ->step(0.000001)
                            ->minValue(0)
                            ->prefix('$'),
                        Select::make('objeto_imp_clave')
                            ->label('Objeto de Impuesto')
                            ->options(ObjetoImp::query()->pluck('descripcion', 'clave'))
                            ->required(),
                    ]),
                Section::make('Configuración de Impuestos')
                    ->schema([
                        Repeater::make('impuestos')
                            ->relationship('impuestos')
                            ->schema([
                                Select::make('impuesto_clave')
                                    ->label('Impuesto')
                                    ->options(Impuesto::query()->pluck('descripcion', 'clave'))
                                    ->required()
                                    ->live(),
                                Select::make('tipo_factor')
                                    ->label('Tipo Factor')
                                    ->options([
                                        'Tasa' => 'Tasa',
                                        'Cuota' => 'Cuota',
                                        'Exento' => 'Exento',
                                    ])
                                    ->required()
                                    ->live(),
                                Select::make('tasa_o_cuota_id')
                                    ->label('Tasa o Cuota')
                                    ->options(function (Get $get): array {
                                        $query = TasaOCuota::query();

                                        if ($get('impuesto_clave')) {
                                            $query->where('impuesto', $get('impuesto_clave'));
                                        }

                                        if ($get('tipo_factor')) {
                                            $query->where('factor', $get('tipo_factor'));
                                        }

                                        return $query->get()
                                            ->mapWithKeys(fn (TasaOCuota $t): array => [
                                                $t->id => $t->valor_maximo,
                                            ])
                                            ->all();
                                    })
                                    ->required(),
                                Toggle::make('es_retencion')
                                    ->label('¿Es retención?')
                                    ->default(false),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Agregar impuesto')
                            ->columns(4)
                            ->hintAction(
                                Action::make('plantilla')
                                    ->label('Aplicar plantilla')
                                    ->icon('heroicon-o-document-duplicate')
                                    ->form([
                                        Select::make('template')
                                            ->label('Plantilla de impuestos')
                                            ->options([
                                                'iva16' => 'Solo IVA 16%',
                                                'iva16_isr10' => 'IVA 16% + ISR 10% retención',
                                                'exento' => 'Exento',
                                                'iva0' => 'IVA 0%',
                                            ])
                                            ->required(),
                                    ])
                                    ->action(function (array $data, Set $set): void {
                                        $set('impuestos', self::getTaxTemplate($data['template']));
                                    })
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clave_prod_serv')
                    ->label('Clave SAT')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->sortable()
                    ->limit(60),
                TextColumn::make('claveUnidad.nombre')
                    ->label('Unidad')
                    ->limit(30),
                TextColumn::make('precio_unitario')
                    ->label('Precio Unitario')
                    ->money('MXN')
                    ->sortable(),
                TextColumn::make('objetoImp.descripcion')
                    ->label('Objeto Imp.')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('impuestos_count')
                    ->label('Impuestos')
                    ->counts('impuestos')
                    ->badge(),
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
            ->defaultSort('descripcion')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductos::route('/'),
            'create' => CreateProducto::route('/create'),
            'edit' => EditProducto::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Returns tax line arrays for preset templates.
     *
     * tasa_o_cuota_id values are resolved at runtime from the database to avoid
     * hardcoding auto-increment IDs that depend on seeder order.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function getTaxTemplate(string $template): array
    {
        return match ($template) {
            'iva16' => [
                [
                    'impuesto_clave' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa_o_cuota_id' => TasaOCuota::query()
                        ->where('impuesto', '002')
                        ->where('factor', 'Tasa')
                        ->where('valor_maximo', '0.160000')
                        ->where('traslado', true)
                        ->value('id'),
                    'es_retencion' => false,
                ],
            ],
            'iva16_isr10' => [
                [
                    'impuesto_clave' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa_o_cuota_id' => TasaOCuota::query()
                        ->where('impuesto', '002')
                        ->where('factor', 'Tasa')
                        ->where('valor_maximo', '0.160000')
                        ->where('traslado', true)
                        ->value('id'),
                    'es_retencion' => false,
                ],
                [
                    'impuesto_clave' => '001',
                    'tipo_factor' => 'Tasa',
                    'tasa_o_cuota_id' => TasaOCuota::query()
                        ->where('impuesto', '001')
                        ->where('factor', 'Tasa')
                        ->where('valor_maximo', '0.100000')
                        ->where('retencion', true)
                        ->value('id'),
                    'es_retencion' => true,
                ],
            ],
            'exento' => [
                [
                    'impuesto_clave' => '002',
                    'tipo_factor' => 'Exento',
                    'tasa_o_cuota_id' => TasaOCuota::query()
                        ->where('impuesto', '002')
                        ->where('factor', 'Exento')
                        ->value('id'),
                    'es_retencion' => false,
                ],
            ],
            'iva0' => [
                [
                    'impuesto_clave' => '002',
                    'tipo_factor' => 'Tasa',
                    'tasa_o_cuota_id' => TasaOCuota::query()
                        ->where('impuesto', '002')
                        ->where('factor', 'Tasa')
                        ->where('valor_maximo', '0.000000')
                        ->where('traslado', true)
                        ->value('id'),
                    'es_retencion' => false,
                ],
            ],
            default => [],
        };
    }
}
