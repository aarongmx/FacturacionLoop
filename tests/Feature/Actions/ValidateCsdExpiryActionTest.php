<?php

declare(strict_types=1);

use App\Actions\ValidateCsdExpiryAction;
use App\Enums\CsdStatus;
use App\Models\Csd;

// CSD-06: Block stamping when no active CSD
it('throws RuntimeException when no active CSD exists', function (): void {
    // No CSDs in database at all
    app(ValidateCsdExpiryAction::class)();
})->throws(RuntimeException::class, 'No hay CSD activo');

it('throws RuntimeException when only inactive CSDs exist', function (): void {
    Csd::factory()->create(['status' => CsdStatus::Inactive]);
    Csd::factory()->expired()->create();

    app(ValidateCsdExpiryAction::class)();
})->throws(RuntimeException::class, 'No hay CSD activo');

// CSD-06: Block stamping when active CSD is expired
it('throws RuntimeException when active CSD is expired', function (): void {
    Csd::factory()->create([
        'status' => CsdStatus::Active,
        'fecha_fin' => now()->subDay(),
    ]);

    app(ValidateCsdExpiryAction::class)();
})->throws(RuntimeException::class, 'El CSD está expirado');

// Success case
it('returns the active CSD when valid and not expired', function (): void {
    $activeCsd = Csd::factory()->active()->create();

    $result = app(ValidateCsdExpiryAction::class)();

    expect($result)->toBeInstanceOf(Csd::class)
        ->and($result->id)->toBe($activeCsd->id)
        ->and($result->status)->toBe(CsdStatus::Active);
});
