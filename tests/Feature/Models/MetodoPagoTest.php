<?php

declare(strict_types=1);

use App\Models\MetodoPago;
use Carbon\CarbonInterface;

it('can be created via factory', function (): void {
    $record = MetodoPago::factory()->create();
    expect($record)->toBeInstanceOf(MetodoPago::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = MetodoPago::factory()->create(['clave' => 'PUE']);
    $found = MetodoPago::find('PUE');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('PUE');
});

it('does not auto-increment', function (): void {
    $model = new MetodoPago;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('casts date fields correctly', function (): void {
    $record = MetodoPago::factory()->create([
        'vigencia_inicio' => '2022-01-01',
        'vigencia_fin' => null,
    ]);
    expect($record->vigencia_inicio)->toBeInstanceOf(CarbonInterface::class)
        ->and($record->vigencia_fin)->toBeNull();
});
