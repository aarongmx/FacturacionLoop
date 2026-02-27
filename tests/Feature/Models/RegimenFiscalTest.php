<?php

declare(strict_types=1);

use App\Models\RegimenFiscal;
use Carbon\CarbonInterface;

it('can be created via factory', function (): void {
    $record = RegimenFiscal::factory()->create();
    expect($record)->toBeInstanceOf(RegimenFiscal::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = RegimenFiscal::factory()->create(['clave' => '601']);
    $found = RegimenFiscal::find('601');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('601');
});

it('does not auto-increment', function (): void {
    $model = new RegimenFiscal;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('casts boolean fields correctly', function (): void {
    $record = RegimenFiscal::factory()->create([
        'aplica_fisica' => true,
        'aplica_moral' => false,
    ]);
    expect($record->aplica_fisica)->toBeTrue()
        ->and($record->aplica_moral)->toBeFalse();
});

it('casts date fields correctly', function (): void {
    $record = RegimenFiscal::factory()->create([
        'vigencia_inicio' => '2022-01-01',
        'vigencia_fin' => null,
    ]);
    expect($record->vigencia_inicio)->toBeInstanceOf(CarbonInterface::class)
        ->and($record->vigencia_fin)->toBeNull();
});
