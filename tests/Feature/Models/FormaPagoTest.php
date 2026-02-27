<?php

declare(strict_types=1);

use App\Models\FormaPago;
use Carbon\CarbonInterface;

it('can be created via factory', function (): void {
    $record = FormaPago::factory()->create();
    expect($record)->toBeInstanceOf(FormaPago::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = FormaPago::factory()->create(['clave' => '01']);
    $found = FormaPago::find('01');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('01');
});

it('does not auto-increment', function (): void {
    $model = new FormaPago;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('casts boolean fields correctly', function (): void {
    $record = FormaPago::factory()->create([
        'bancarizado' => true,
    ]);
    expect($record->bancarizado)->toBeTrue();
});

it('casts date fields correctly', function (): void {
    $record = FormaPago::factory()->create([
        'vigencia_inicio' => '2022-01-01',
        'vigencia_fin' => null,
    ]);
    expect($record->vigencia_inicio)->toBeInstanceOf(CarbonInterface::class)
        ->and($record->vigencia_fin)->toBeNull();
});
