<?php

declare(strict_types=1);

use App\Models\ClaveProdServ;
use App\Models\ClaveUnidad;
use App\Models\Impuesto;
use App\Models\ObjetoImp;
use App\Models\Producto;
use App\Models\ProductoImpuesto;
use App\Models\TasaOCuota;

beforeEach(function (): void {
    // Seed the SAT catalog records required by ProductoFactory defaults
    ClaveProdServ::factory()->create(['clave' => '01010101', 'descripcion' => 'No existe en el catálogo']);
    ClaveUnidad::factory()->create(['clave' => 'E48', 'nombre' => 'Unidad de servicio']);
    ObjetoImp::factory()->create(['clave' => '02', 'descripcion' => 'Sí objeto de impuesto']);
    Impuesto::factory()->create(['clave' => '002', 'descripcion' => 'IVA']);
    Impuesto::factory()->create(['clave' => '001', 'descripcion' => 'ISR']);
});

it('can be created via factory', function (): void {
    $producto = Producto::factory()->create();
    expect($producto)->toBeInstanceOf(Producto::class)
        ->and($producto->id)->toBeInt()
        ->and($producto->id)->toBeGreaterThan(0);
});

it('has fillable attributes', function (): void {
    $producto = Producto::factory()->create([
        'descripcion' => 'Servicio de consultoría',
        'precio_unitario' => 1500.500000,
    ]);
    expect($producto->descripcion)->toBe('Servicio de consultoría')
        ->and($producto->precio_unitario)->toBe('1500.500000');
});

it('casts precio_unitario to decimal with 6 places', function (): void {
    $producto = Producto::factory()->create([
        'precio_unitario' => 100.123456,
    ]);
    expect($producto->precio_unitario)->toBe('100.123456');
});

it('supports soft delete', function (): void {
    $producto = Producto::factory()->create();
    $id = $producto->id;

    $producto->delete();

    expect(Producto::find($id))->toBeNull()
        ->and(Producto::withTrashed()->find($id))->not->toBeNull();
});

it('can be restored after soft delete', function (): void {
    $producto = Producto::factory()->create();
    $id = $producto->id;

    $producto->delete();
    Producto::withTrashed()->find($id)->restore();

    expect(Producto::find($id))->not->toBeNull();
});

it('has claveProdServ BelongsTo relationship', function (): void {
    $producto = Producto::factory()->create();
    expect($producto->claveProdServ)->toBeInstanceOf(ClaveProdServ::class)
        ->and($producto->claveProdServ->clave)->toBe('01010101');
});

it('has claveUnidad BelongsTo relationship', function (): void {
    $producto = Producto::factory()->create();
    expect($producto->claveUnidad)->toBeInstanceOf(ClaveUnidad::class)
        ->and($producto->claveUnidad->clave)->toBe('E48');
});

it('has objetoImp BelongsTo relationship', function (): void {
    $producto = Producto::factory()->create();
    expect($producto->objetoImp)->toBeInstanceOf(ObjetoImp::class)
        ->and($producto->objetoImp->clave)->toBe('02');
});

it('has impuestos HasMany relationship', function (): void {
    $tasa = TasaOCuota::factory()->create([
        'impuesto' => '002',
        'factor' => 'Tasa',
        'valor_maximo' => '0.160000',
        'traslado' => true,
        'retencion' => false,
    ]);

    $producto = Producto::factory()->create();

    ProductoImpuesto::factory()->create([
        'producto_id' => $producto->id,
        'impuesto_clave' => '002',
        'tipo_factor' => 'Tasa',
        'tasa_o_cuota_id' => $tasa->id,
        'es_retencion' => false,
    ]);

    expect($producto->impuestos)->toHaveCount(1)
        ->and($producto->impuestos->first())->toBeInstanceOf(ProductoImpuesto::class)
        ->and($producto->impuestos->first()->impuesto_clave)->toBe('002');
});

it('cascades delete to impuestos when force deleted', function (): void {
    $tasa = TasaOCuota::factory()->create([
        'impuesto' => '002',
        'factor' => 'Tasa',
    ]);

    $producto = Producto::factory()->create();

    ProductoImpuesto::factory()->create([
        'producto_id' => $producto->id,
        'tasa_o_cuota_id' => $tasa->id,
    ]);

    expect(ProductoImpuesto::where('producto_id', $producto->id)->count())->toBe(1);

    $producto->forceDelete();

    expect(ProductoImpuesto::where('producto_id', $producto->id)->count())->toBe(0);
});

it('can have multiple tax lines', function (): void {
    $tasaIva = TasaOCuota::factory()->create([
        'impuesto' => '002',
        'factor' => 'Tasa',
        'valor_maximo' => '0.160000',
    ]);
    $tasaIsr = TasaOCuota::factory()->create([
        'impuesto' => '001',
        'factor' => 'Tasa',
        'valor_maximo' => '0.100000',
    ]);

    $producto = Producto::factory()->create();

    ProductoImpuesto::factory()->create([
        'producto_id' => $producto->id,
        'impuesto_clave' => '002',
        'tasa_o_cuota_id' => $tasaIva->id,
        'es_retencion' => false,
    ]);
    ProductoImpuesto::factory()->create([
        'producto_id' => $producto->id,
        'impuesto_clave' => '001',
        'tasa_o_cuota_id' => $tasaIsr->id,
        'es_retencion' => true,
    ]);

    expect($producto->fresh()->impuestos)->toHaveCount(2);
});

it('uses productos table', function (): void {
    $producto = new Producto;
    expect($producto->getTable())->toBe('productos');
});
