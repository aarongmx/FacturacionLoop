<?php

declare(strict_types=1);

use App\Actions\ActivateCsdAction;
use App\Enums\CsdStatus;
use App\Models\Csd;

it('activates a CSD and sets status to Active', function (): void {
    $csd = Csd::factory()->create(['status' => CsdStatus::Inactive]);

    $result = app(ActivateCsdAction::class)($csd);

    expect($result->status)->toBe(CsdStatus::Active);
});

it('deactivates the previously active CSD when activating a new one', function (): void {
    $previousActive = Csd::factory()->active()->create();
    $newCsd = Csd::factory()->create(['status' => CsdStatus::Inactive]);

    app(ActivateCsdAction::class)($newCsd);

    expect($previousActive->refresh()->status)->toBe(CsdStatus::Inactive)
        ->and($newCsd->refresh()->status)->toBe(CsdStatus::Active);
});

it('ensures only one CSD is active at a time', function (): void {
    Csd::factory()->active()->create();
    Csd::factory()->active()->create();
    $third = Csd::factory()->create(['status' => CsdStatus::Inactive]);

    app(ActivateCsdAction::class)($third);

    $activeCount = Csd::query()->whereActive()->count();
    expect($activeCount)->toBe(1);
});

it('throws RuntimeException when activating an expired CSD', function (): void {
    $expired = Csd::factory()->expired()->create();

    app(ActivateCsdAction::class)($expired);
})->throws(RuntimeException::class, 'No se puede activar un CSD expirado');
