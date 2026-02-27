<?php

declare(strict_types=1);

use App\Models\Impuesto;
use App\Models\TasaOCuota;
use Illuminate\Database\QueryException;

it('can be created via factory', function (): void {
    $record = TasaOCuota::factory()->create();
    expect($record)->toBeInstanceOf(TasaOCuota::class)
        ->and($record->id)->toBeInt();
});

it('uses auto-increment primary key', function (): void {
    $model = new TasaOCuota;
    expect($model->incrementing)->toBeTrue();
});

it('belongs to an impuesto', function (): void {
    $impuesto = Impuesto::factory()->create(['clave' => '002']);
    $tasaOCuota = TasaOCuota::factory()->create([
        'impuesto' => '002',
        'traslado' => true,
        'retencion' => false,
    ]);

    $relatedImpuesto = $tasaOCuota->impuesto()->first();

    expect($tasaOCuota->impuesto())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($relatedImpuesto)->toBeInstanceOf(Impuesto::class)
        ->and($relatedImpuesto->clave)->toBe('002');
});

it('enforces composite unique constraint', function (): void {
    $attrs = [
        'impuesto' => '002',
        'factor' => 'Tasa',
        'valor_minimo' => '0.160000',
        'valor_maximo' => '0.160000',
        'traslado' => true,
        'retencion' => false,
    ];

    TasaOCuota::factory()->create($attrs);

    expect(fn () => TasaOCuota::factory()->create($attrs))
        ->toThrow(QueryException::class);
});

it('casts boolean fields correctly', function (): void {
    $record = TasaOCuota::factory()->create([
        'traslado' => true,
        'retencion' => false,
    ]);
    expect($record->traslado)->toBeTrue()
        ->and($record->retencion)->toBeFalse();
});
