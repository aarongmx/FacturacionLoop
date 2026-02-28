<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsdResource\Pages;

use App\Actions\UploadCsdAction;
use App\Data\UploadCsdData;
use App\Filament\Resources\CsdResource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Override;
use RuntimeException;

final class CreateCsd extends CreateRecord
{
    #[Override]
    protected static string $resource = CsdResource::class;

    #[Override]
    protected static ?string $title = 'Subir Certificado de Sello Digital';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('cer_file')
                ->label('Archivo .cer')
                ->disk('local')
                ->directory('csd/temp')
                ->visibility('private')
                ->acceptedFileTypes([
                    'application/x-x509-ca-cert',
                    'application/pkix-cert',
                    'application/octet-stream',
                ])
                ->maxSize(512)
                ->required(),

            FileUpload::make('key_file')
                ->label('Archivo .key')
                ->disk('local')
                ->directory('csd/temp')
                ->visibility('private')
                ->acceptedFileTypes([
                    'application/pkcs8',
                    'application/octet-stream',
                ])
                ->maxSize(512)
                ->required(),

            TextInput::make('passphrase')
                ->label('Contraseña del archivo .key')
                ->password()
                ->revealable()
                ->required(),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $cerPath = Storage::disk('local')->path($data['cer_file']);
        $keyPath = Storage::disk('local')->path($data['key_file']);

        try {
            $csd = resolve(UploadCsdAction::class)(
                new UploadCsdData(
                    cerFilePath: $cerPath,
                    keyFilePath: $keyPath,
                    passphrase: $data['passphrase'],
                ),
            );
        } catch (RuntimeException $runtimeException) {
            Notification::make()
                ->danger()
                ->title('Error al procesar el certificado')
                ->body($runtimeException->getMessage())
                ->persistent()
                ->send();

            $this->halt();
        }

        Notification::make()
            ->success()
            ->title('CSD cargado correctamente')
            ->body(sprintf('Certificado %s registrado exitosamente.', $csd->no_certificado))
            ->send();

        return $csd;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
