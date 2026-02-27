<?php

declare(strict_types=1);

use App\Models\ObjetoImp;
use Carbon\CarbonInterface;

it('can be created via factory', function (): void {
    $record = ObjetoImp::factory()->create();
    expect($record)->toBeInstanceOf(ObjetoImp::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = ObjetoImp::factory()->create(['clave' => '01']);
    $found = ObjetoImp::find('01');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('01');
});

it('does not auto-increment', function (): void {
    $model = new ObjetoImp;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('casts date fields correctly', function (): void {
    $record = ObjetoImp::factory()->create([
        'vigencia_inicio' => '2022-01-01',
        'vigencia_fin' => null,
    ]);
    expect($record->vigencia_inicio)->toBeInstanceOf(CarbonInterface::class)
        ->and($record->vigencia_fin)->toBeNull();
});
