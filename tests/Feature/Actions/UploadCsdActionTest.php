<?php

declare(strict_types=1);

use App\Actions\UploadCsdAction;
use App\Data\UploadCsdData;
use App\Enums\CsdStatus;
use App\Models\Csd;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

// CSD-01, CSD-02: Upload .cer and .key successfully
it('creates a CSD record from valid .cer and .key files', function (): void {
    Storage::fake('local');
    $cerPath = base_path('tests/fixtures/csd/EKU9003173C9.cer');
    $keyPath = base_path('tests/fixtures/csd/EKU9003173C9.key');
    $passphrase = '12345678a';

    if (! file_exists($cerPath) || ! file_exists($keyPath)) {
        test()->markTestSkipped('CSD test fixtures not found. Run tests/fixtures/csd/generate.sh to create them.');
    }

    $tempCer = tempnam(sys_get_temp_dir(), 'cer');
    $tempKey = tempnam(sys_get_temp_dir(), 'key');
    copy($cerPath, $tempCer);
    copy($keyPath, $tempKey);

    $data = new UploadCsdData(
        cerFilePath: $tempCer,
        keyFilePath: $tempKey,
        passphrase: $passphrase,
    );

    $csd = app(UploadCsdAction::class)($data);

    expect($csd)->toBeInstanceOf(Csd::class)
        ->and($csd->no_certificado)->toBeString()->not->toBeEmpty()
        ->and($csd->rfc)->toBeString()->not->toBeEmpty();
});

// CSD-05: Extract NoCertificado and validity dates
it('extracts NoCertificado, RFC, and validity dates from .cer', function (): void {
    Storage::fake('local');
    $cerPath = base_path('tests/fixtures/csd/EKU9003173C9.cer');
    $keyPath = base_path('tests/fixtures/csd/EKU9003173C9.key');
    $passphrase = '12345678a';

    if (! file_exists($cerPath) || ! file_exists($keyPath)) {
        test()->markTestSkipped('CSD test fixtures not found.');
    }

    $tempCer = tempnam(sys_get_temp_dir(), 'cer');
    $tempKey = tempnam(sys_get_temp_dir(), 'key');
    copy($cerPath, $tempCer);
    copy($keyPath, $tempKey);

    $csd = app(UploadCsdAction::class)(new UploadCsdData($tempCer, $tempKey, $passphrase));

    expect($csd->no_certificado)->not->toBeEmpty()
        ->and($csd->rfc)->toBe('EKU9003173C9')
        ->and($csd->fecha_inicio)->not->toBeNull()
        ->and($csd->fecha_fin)->not->toBeNull()
        ->and($csd->fecha_fin->gt($csd->fecha_inicio))->toBeTrue();
});

// CSD-04: .key file encrypted in private storage
it('stores .key file encrypted in private storage', function (): void {
    Storage::fake('local');
    $cerPath = base_path('tests/fixtures/csd/EKU9003173C9.cer');
    $keyPath = base_path('tests/fixtures/csd/EKU9003173C9.key');
    $passphrase = '12345678a';

    if (! file_exists($cerPath) || ! file_exists($keyPath)) {
        test()->markTestSkipped('CSD test fixtures not found.');
    }

    $originalKeyContents = file_get_contents($keyPath);

    $tempCer = tempnam(sys_get_temp_dir(), 'cer');
    $tempKey = tempnam(sys_get_temp_dir(), 'key');
    copy($cerPath, $tempCer);
    copy($keyPath, $tempKey);

    $csd = app(UploadCsdAction::class)(new UploadCsdData($tempCer, $tempKey, $passphrase));

    // Verify file is stored
    Storage::disk('local')->assertExists($csd->key_path);

    // Verify stored contents are encrypted (not raw binary)
    $storedContents = Storage::disk('local')->get($csd->key_path);
    expect($storedContents)->not->toBe($originalKeyContents);

    // Verify stored contents can be decrypted back to original
    $decrypted = Crypt::decryptString($storedContents);
    expect($decrypted)->toBe($originalKeyContents);
});

// CSD-01: .cer file stored in private storage
it('stores .cer file in private storage', function (): void {
    Storage::fake('local');
    $cerPath = base_path('tests/fixtures/csd/EKU9003173C9.cer');
    $keyPath = base_path('tests/fixtures/csd/EKU9003173C9.key');
    $passphrase = '12345678a';

    if (! file_exists($cerPath) || ! file_exists($keyPath)) {
        test()->markTestSkipped('CSD test fixtures not found.');
    }

    $tempCer = tempnam(sys_get_temp_dir(), 'cer');
    $tempKey = tempnam(sys_get_temp_dir(), 'key');
    copy($cerPath, $tempCer);
    copy($keyPath, $tempKey);

    $csd = app(UploadCsdAction::class)(new UploadCsdData($tempCer, $tempKey, $passphrase));

    Storage::disk('local')->assertExists($csd->cer_path);
});

// CSD-02: Sets initial status based on validity
it('sets initial CSD status based on certificate validity', function (): void {
    Storage::fake('local');
    $cerPath = base_path('tests/fixtures/csd/EKU9003173C9.cer');
    $keyPath = base_path('tests/fixtures/csd/EKU9003173C9.key');
    $passphrase = '12345678a';

    if (! file_exists($cerPath) || ! file_exists($keyPath)) {
        test()->markTestSkipped('CSD test fixtures not found.');
    }

    $tempCer = tempnam(sys_get_temp_dir(), 'cer');
    $tempKey = tempnam(sys_get_temp_dir(), 'key');
    copy($cerPath, $tempCer);
    copy($keyPath, $tempKey);

    $csd = app(UploadCsdAction::class)(new UploadCsdData($tempCer, $tempKey, $passphrase));

    // The test certificate expires in 2031, so it should be Inactive (not ExpiringSoon)
    expect($csd->status)->toBeIn([CsdStatus::Inactive, CsdStatus::ExpiringSoon]);
    // Must NOT be auto-activated
    expect($csd->status)->not->toBe(CsdStatus::Active);
});

// Temp file cleanup
it('cleans up temp files after successful upload', function (): void {
    Storage::fake('local');
    $cerPath = base_path('tests/fixtures/csd/EKU9003173C9.cer');
    $keyPath = base_path('tests/fixtures/csd/EKU9003173C9.key');
    $passphrase = '12345678a';

    if (! file_exists($cerPath) || ! file_exists($keyPath)) {
        test()->markTestSkipped('CSD test fixtures not found.');
    }

    $tempCer = tempnam(sys_get_temp_dir(), 'cer');
    $tempKey = tempnam(sys_get_temp_dir(), 'key');
    copy($cerPath, $tempCer);
    copy($keyPath, $tempKey);

    app(UploadCsdAction::class)(new UploadCsdData($tempCer, $tempKey, $passphrase));

    expect(file_exists($tempCer))->toBeFalse()
        ->and(file_exists($tempKey))->toBeFalse();
});

// Error: invalid pair (invalid file contents)
it('throws RuntimeException for invalid .cer/.key contents', function (): void {
    Storage::fake('local');
    $tempCer = tempnam(sys_get_temp_dir(), 'cer');
    $tempKey = tempnam(sys_get_temp_dir(), 'key');
    file_put_contents($tempCer, 'invalid-cer-data');
    file_put_contents($tempKey, 'invalid-key-data');

    app(UploadCsdAction::class)(new UploadCsdData($tempCer, $tempKey, 'wrong'));
})->throws(RuntimeException::class);

// Error: wrong passphrase
it('throws RuntimeException for wrong passphrase', function (): void {
    Storage::fake('local');
    $cerPath = base_path('tests/fixtures/csd/EKU9003173C9.cer');
    $keyPath = base_path('tests/fixtures/csd/EKU9003173C9.key');

    if (! file_exists($cerPath) || ! file_exists($keyPath)) {
        test()->markTestSkipped('CSD test fixtures not found.');
    }

    $tempCer = tempnam(sys_get_temp_dir(), 'cer');
    $tempKey = tempnam(sys_get_temp_dir(), 'key');
    copy($cerPath, $tempCer);
    copy($keyPath, $tempKey);

    app(UploadCsdAction::class)(new UploadCsdData($tempCer, $tempKey, 'wrong-passphrase'));
})->throws(RuntimeException::class);
