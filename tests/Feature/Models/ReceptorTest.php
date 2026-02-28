<?php

declare(strict_types=1);

use App\Models\Receptor;
use App\Models\RegimenFiscal;
use App\Models\UsoCfdi;

it('can be created via factory', function (): void {
    $receptor = Receptor::factory()->create();
    expect($receptor)->toBeInstanceOf(Receptor::class)
        ->and($receptor->id)->toBeInt()
        ->and($receptor->id)->toBeGreaterThan(0);
});

it('has fillable attributes', function (): void {
    $receptor = Receptor::factory()->create([
        'rfc' => 'ABCD010101AB1',
        'nombre_fiscal' => 'Cliente Test',
        'domicilio_fiscal_cp' => '03100',
    ]);
    expect($receptor->rfc)->toBe('ABCD010101AB1')
        ->and($receptor->nombre_fiscal)->toBe('Cliente Test')
        ->and($receptor->domicilio_fiscal_cp)->toBe('03100');
});

it('supports soft delete', function (): void {
    $receptor = Receptor::factory()->create();
    $id = $receptor->id;

    $receptor->delete();

    expect(Receptor::find($id))->toBeNull()
        ->and(Receptor::withTrashed()->find($id))->not->toBeNull();
});

it('can be restored after soft delete', function (): void {
    $receptor = Receptor::factory()->create();
    $id = $receptor->id;

    $receptor->delete();
    expect(Receptor::find($id))->toBeNull();

    Receptor::withTrashed()->find($id)->restore();
    expect(Receptor::find($id))->not->toBeNull();
});

it('has regimenFiscal BelongsTo relationship', function (): void {
    $regimen = RegimenFiscal::factory()->create();
    $receptor = Receptor::factory()->create([
        'regimen_fiscal_clave' => $regimen->clave,
    ]);

    expect($receptor->regimenFiscal)->toBeInstanceOf(RegimenFiscal::class)
        ->and($receptor->regimenFiscal->clave)->toBe($regimen->clave);
});

it('has usoCfdi BelongsTo relationship', function (): void {
    $usoCfdi = UsoCfdi::factory()->create();
    $receptor = Receptor::factory()->create([
        'uso_cfdi_clave' => $usoCfdi->clave,
    ]);

    expect($receptor->usoCfdi)->toBeInstanceOf(UsoCfdi::class)
        ->and($receptor->usoCfdi->clave)->toBe($usoCfdi->clave);
});

it('allows nullable FK fields', function (): void {
    $receptor = Receptor::factory()->create([
        'regimen_fiscal_clave' => null,
        'uso_cfdi_clave' => null,
    ]);
    expect($receptor->regimen_fiscal_clave)->toBeNull()
        ->and($receptor->uso_cfdi_clave)->toBeNull();
});

it('allows duplicate RFCs', function (): void {
    $rfc = 'ABCD010101AB1';
    Receptor::factory()->create(['rfc' => $rfc]);
    $second = Receptor::factory()->create(['rfc' => $rfc]);

    expect($second->id)->toBeGreaterThan(0);
    expect(Receptor::where('rfc', $rfc)->count())->toBe(2);
});

it('creates público en general via factory state', function (): void {
    // publicoEnGeneral() hardcodes regimen_fiscal_clave='616' and uso_cfdi_clave='S01'
    RegimenFiscal::factory()->create(['clave' => '616']);
    UsoCfdi::factory()->create(['clave' => 'S01']);

    $receptor = Receptor::factory()->publicoEnGeneral()->create();
    expect($receptor->rfc)->toBe('XAXX010101000')
        ->and($receptor->nombre_fiscal)->toBe('PUBLICO EN GENERAL')
        ->and($receptor->regimen_fiscal_clave)->toBe('616')
        ->and($receptor->uso_cfdi_clave)->toBe('S01');
});

it('creates persona moral via factory state', function (): void {
    $receptor = Receptor::factory()->personaMoral()->create();
    expect(mb_strlen($receptor->rfc))->toBe(12);
});

it('creates extranjero via factory state', function (): void {
    $receptor = Receptor::factory()->extranjero()->create();
    expect($receptor->rfc)->toBe('XEXX010101000');
});

it('uses receptores table', function (): void {
    $receptor = new Receptor;
    expect($receptor->getTable())->toBe('receptores');
});
