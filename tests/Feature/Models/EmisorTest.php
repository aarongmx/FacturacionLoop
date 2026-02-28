<?php

declare(strict_types=1);

use App\Models\Emisor;
use App\Models\RegimenFiscal;

it('can be created via factory', function (): void {
    $emisor = Emisor::factory()->create();
    expect($emisor)->toBeInstanceOf(Emisor::class)
        ->and($emisor->id)->toBeInt()
        ->and($emisor->id)->toBeGreaterThan(0);
});

it('has fillable attributes', function (): void {
    $emisor = Emisor::factory()->create([
        'rfc' => 'AAA010101AAA',
        'razon_social' => 'Mi Empresa SA de CV',
        'domicilio_fiscal_cp' => '06600',
    ]);
    expect($emisor->rfc)->toBe('AAA010101AAA')
        ->and($emisor->razon_social)->toBe('Mi Empresa SA de CV')
        ->and($emisor->domicilio_fiscal_cp)->toBe('06600');
});

it('allows nullable logo_path', function (): void {
    $emisor = Emisor::factory()->create(['logo_path' => null]);
    expect($emisor->logo_path)->toBeNull();

    $emisorWithLogo = Emisor::factory()->create(['logo_path' => 'emisor/logo.png']);
    expect($emisorWithLogo->logo_path)->toBe('emisor/logo.png');
});

it('has regimenesFiscales BelongsToMany relationship', function (): void {
    $regimen = RegimenFiscal::factory()->create();
    $emisor = Emisor::factory()->create();

    $emisor->regimenesFiscales()->attach($regimen->clave);

    $loaded = $emisor->regimenesFiscales()->get();
    expect($loaded)->toHaveCount(1)
        ->and($loaded->first()->clave)->toBe($regimen->clave);
});

it('can sync multiple regimenes fiscales', function (): void {
    $regimen1 = RegimenFiscal::factory()->create();
    $regimen2 = RegimenFiscal::factory()->create();
    $emisor = Emisor::factory()->create();

    $emisor->regimenesFiscales()->sync([$regimen1->clave, $regimen2->clave]);

    expect($emisor->regimenesFiscales()->count())->toBe(2);

    // Sync with only one should remove the other
    $emisor->regimenesFiscales()->sync([$regimen1->clave]);
    expect($emisor->fresh()->regimenesFiscales()->count())->toBe(1);
});

it('uses emisores table', function (): void {
    $emisor = new Emisor;
    expect($emisor->getTable())->toBe('emisores');
});
