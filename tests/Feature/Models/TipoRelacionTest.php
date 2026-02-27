<?php

declare(strict_types=1);

use App\Models\TipoRelacion;
use Carbon\CarbonInterface;

it('can be created via factory', function (): void {
    $record = TipoRelacion::factory()->create();
    expect($record)->toBeInstanceOf(TipoRelacion::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = TipoRelacion::factory()->create(['clave' => '01']);
    $found = TipoRelacion::find('01');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('01');
});

it('does not auto-increment', function (): void {
    $model = new TipoRelacion;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('casts date fields correctly', function (): void {
    $record = TipoRelacion::factory()->create([
        'vigencia_inicio' => '2022-01-01',
        'vigencia_fin' => null,
    ]);
    expect($record->vigencia_inicio)->toBeInstanceOf(CarbonInterface::class)
        ->and($record->vigencia_fin)->toBeNull();
});
