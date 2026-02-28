<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Emisor;
use App\Models\RegimenFiscal;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Override;

final class EmisorSettings extends Page implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** @var array<int, string> */
    public array $regimenes = [];

    protected string $view = 'filament.pages.emisor-settings';

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    // Top-level nav item — NOT nested under a group (per locked decision)

    #[Override]
    protected static ?string $navigationLabel = 'Emisor';

    #[Override]
    protected static ?string $title = 'Configuración del Emisor';

    public function mount(): void
    {
        $emisor = Emisor::firstOrCreate(
            ['id' => 1],
            [
                'rfc' => '',
                'razon_social' => '',
                'domicilio_fiscal_cp' => '',
            ],
        );

        $this->data = $emisor->only(['rfc', 'razon_social', 'domicilio_fiscal_cp', 'logo_path']);
        $this->regimenes = $emisor->regimenesFiscales()->pluck('clave')->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('rfc')
                    ->label('RFC del Emisor')
                    ->required()
                    ->maxLength(13)
                    ->minLength(12)
                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? mb_strtoupper(mb_trim($state)) : null),
                TextInput::make('razon_social')
                    ->label('Razón Social')
                    ->required()
                    ->maxLength(300),
                TextInput::make('domicilio_fiscal_cp')
                    ->label('Código Postal del Domicilio Fiscal')
                    ->required()
                    ->maxLength(5)
                    ->minLength(5)
                    ->numeric(),
                Select::make('regimenes')
                    ->label('Regímenes Fiscales')
                    ->options(RegimenFiscal::query()->pluck('descripcion', 'clave'))
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->statePath('regimenes'),
                FileUpload::make('logo_path')
                    ->label('Logo (opcional)')
                    ->image()
                    ->directory('emisor')
                    ->maxSize(2048)
                    ->nullable(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $emisor = Emisor::firstOrCreate(['id' => 1]);
        $emisor->update([
            'rfc' => $state['rfc'],
            'razon_social' => $state['razon_social'],
            'domicilio_fiscal_cp' => $state['domicilio_fiscal_cp'],
            'logo_path' => $state['logo_path'] ?? null,
        ]);

        // Sync regimenes fiscales via the pivot table
        $emisor->regimenesFiscales()->sync($this->regimenes);

        Notification::make()
            ->title('Datos del emisor guardados')
            ->success()
            ->send();
    }
}
