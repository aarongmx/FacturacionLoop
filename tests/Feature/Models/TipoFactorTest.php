<?php

declare(strict_types=1);

use App\Models\TipoFactor;
use Carbon\CarbonInterface;

it('can be created via factory', function (): void {
    $record = TipoFactor::factory()->create();
    expect($record)->toBeInstanceOf(TipoFactor::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = TipoFactor::factory()->create(['clave' => 'Tasa']);
    $found = TipoFactor::find('Tasa');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('Tasa');
});

it('does not auto-increment', function (): void {
    $model = new TipoFactor;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('casts date fields correctly', function (): void {
    $record = TipoFactor::factory()->create([
        'vigencia_inicio' => '2022-01-01',
        'vigencia_fin' => null,
    ]);
    expect($record->vigencia_inicio)->toBeInstanceOf(CarbonInterface::class)
        ->and($record->vigencia_fin)->toBeNull();
});
