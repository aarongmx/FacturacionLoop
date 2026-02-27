<?php

declare(strict_types=1);

use App\Models\ClaveUnidad;

it('can be created via factory', function (): void {
    $record = ClaveUnidad::factory()->create();
    expect($record)->toBeInstanceOf(ClaveUnidad::class)
        ->and($record->clave)->toBeString();
});

it('uses string primary key', function (): void {
    $record = ClaveUnidad::factory()->create(['clave' => 'KGM']);
    $found = ClaveUnidad::find('KGM');
    expect($found)->not->toBeNull()
        ->and($found->clave)->toBe('KGM');
});

it('does not auto-increment', function (): void {
    $model = new ClaveUnidad;
    expect($model->incrementing)->toBeFalse()
        ->and($model->getKeyType())->toBe('string');
});

it('can be searched by nombre', function (): void {
    ClaveUnidad::factory()->create(['clave' => 'KGM', 'nombre' => 'Kilogramo']);
    ClaveUnidad::factory()->create(['clave' => 'LTR', 'nombre' => 'Litro']);
    ClaveUnidad::factory()->create(['clave' => 'MTR', 'nombre' => 'Metro']);

    $results = ClaveUnidad::where('nombre', 'like', '%Kilogr%')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->clave)->toBe('KGM');
});
