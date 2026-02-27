<?php

declare(strict_types=1);

use App\Models\Impuesto;
use App\Models\TasaOCuota;
use Carbon\CarbonInterface;

it('can be created via factory', function (): void {
    $record = Impuesto::factory()->create();
    expect($record)->toBeInstanceOf(Impuesto::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = Impuesto::factory()->create(['clave' => '002']);
    $found = Impuesto::find('002');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('002');
});

it('does not auto-increment', function (): void {
    $model = new Impuesto;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('casts date fields correctly', function (): void {
    $record = Impuesto::factory()->create([
        'vigencia_inicio' => '2022-01-01',
        'vigencia_fin' => null,
    ]);
    expect($record->vigencia_inicio)->toBeInstanceOf(CarbonInterface::class)
        ->and($record->vigencia_fin)->toBeNull();
});

it('has many tasas o cuotas', function (): void {
    $impuesto = Impuesto::factory()->create(['clave' => '002']);
    TasaOCuota::factory()->count(2)->create(['impuesto' => '002']);
    expect($impuesto->tasasOCuotas)->toHaveCount(2)
        ->each->toBeInstanceOf(TasaOCuota::class);
});
