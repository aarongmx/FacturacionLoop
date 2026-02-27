<?php

declare(strict_types=1);

use App\Models\TipoDeComprobante;
use Carbon\CarbonInterface;

it('can be created via factory', function (): void {
    $record = TipoDeComprobante::factory()->create();
    expect($record)->toBeInstanceOf(TipoDeComprobante::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = TipoDeComprobante::factory()->create(['clave' => 'I']);
    $found = TipoDeComprobante::find('I');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('I');
});

it('does not auto-increment', function (): void {
    $model = new TipoDeComprobante;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('casts date fields correctly', function (): void {
    $record = TipoDeComprobante::factory()->create([
        'vigencia_inicio' => '2022-01-01',
        'vigencia_fin' => null,
    ]);
    expect($record->vigencia_inicio)->toBeInstanceOf(CarbonInterface::class)
        ->and($record->vigencia_fin)->toBeNull();
});
