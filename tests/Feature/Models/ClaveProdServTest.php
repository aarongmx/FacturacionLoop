<?php

declare(strict_types=1);

use App\Models\ClaveProdServ;

it('can be created via factory', function (): void {
    $record = ClaveProdServ::factory()->create();
    expect($record)->toBeInstanceOf(ClaveProdServ::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = ClaveProdServ::factory()->create(['clave' => '10101500']);
    $found = ClaveProdServ::find('10101500');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('10101500');
});

it('does not auto-increment', function (): void {
    $model = new ClaveProdServ;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('can be searched by descripcion', function (): void {
    ClaveProdServ::factory()->create(['clave' => '10101501', 'descripcion' => 'Animales domésticos vivos']);
    ClaveProdServ::factory()->create(['clave' => '10101502', 'descripcion' => 'Plantas ornamentales']);
    ClaveProdServ::factory()->create(['clave' => '10101503', 'descripcion' => 'Maquinaria industrial']);

    $results = ClaveProdServ::where('descripcion', 'like', '%domésticos%')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->clave)->toBe('10101501');
});

it('casts estimulo_franja as boolean', function (): void {
    $record = ClaveProdServ::factory()->create(['estimulo_franja' => true]);
    expect($record->estimulo_franja)->toBeTrue();

    $record2 = ClaveProdServ::factory()->create(['estimulo_franja' => false]);
    expect($record2->estimulo_franja)->toBeFalse();
});
