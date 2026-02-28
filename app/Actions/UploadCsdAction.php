<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\UploadCsdData;
use App\Enums\CsdStatus;
use App\Models\Csd;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpCfdi\Credentials\Credential;
use RuntimeException;
use UnexpectedValueException;

final class UploadCsdAction
{
    /**
     * @throws RuntimeException When .cer/.key pair is invalid, passphrase is wrong, or certificate is not a CSD
     */
    public function __invoke(UploadCsdData $data): Csd
    {
        // Step 1: Validate the .cer/.key pair and passphrase
        try {
            $credential = Credential::openFiles(
                $data->cerFilePath,
                $data->keyFilePath,
                $data->passphrase,
            );
        } catch (UnexpectedValueException $e) {
            throw new RuntimeException(
                'El certificado y la llave privada no coinciden, o la contraseña es incorrecta.',
                previous: $e,
            );
        }

        // Step 2: Verify it is a CSD (not FIEL)
        if (! $credential->isCsd()) {
            throw new RuntimeException(
                'El archivo .cer no es un Certificado de Sello Digital (CSD). Verifique que no sea una FIEL.',
            );
        }

        $certificate = $credential->certificate();

        // Step 3: Extract metadata
        // Use validFromDateTime()/validToDateTime() which return DateTimeImmutable — safer than string parsing
        $noCertificado = $certificate->serialNumber()->bytes();
        $rfc = $certificate->rfc();
        $fechaInicio = Carbon::instance($certificate->validFromDateTime());
        $fechaFin = Carbon::instance($certificate->validToDateTime());

        // Step 4: Determine initial status based on expiry
        $status = match (true) {
            $fechaFin->isPast() => CsdStatus::Expired,
            $fechaFin->lte(now()->addDays(90)) => CsdStatus::ExpiringSoon,
            default => CsdStatus::Inactive,
        };

        // Step 5: Encrypt and store .key file contents in private storage
        $keyContents = file_get_contents($data->keyFilePath);
        $encryptedKey = Crypt::encryptString($keyContents);
        $keyStorePath = 'csd/'.$noCertificado.'.key.enc';
        Storage::disk('local')->put($keyStorePath, $encryptedKey);

        // Step 6: Store .cer file in private storage (not encrypted — needed as-is for XML signing)
        $cerContents = file_get_contents($data->cerFilePath);
        $cerStorePath = 'csd/'.$noCertificado.'.cer';
        Storage::disk('local')->put($cerStorePath, $cerContents);

        // Step 7: Persist the CSD record within a transaction
        $csd = DB::transaction(fn (): Csd => Csd::create([
            'no_certificado' => $noCertificado,
            'rfc' => $rfc,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'status' => $status,
            'key_path' => $keyStorePath,
            'passphrase_encrypted' => $data->passphrase, // 'encrypted' cast handles encryption on save
            'cer_path' => $cerStorePath,
        ]));

        // Step 8: Clean up temp upload files
        // Delete temp files if they exist in the temp upload directory
        if (file_exists($data->cerFilePath)) {
            @unlink($data->cerFilePath);
        }

        if (file_exists($data->keyFilePath)) {
            @unlink($data->keyFilePath);
        }

        return $csd;
    }
}
