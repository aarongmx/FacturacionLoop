<?php

declare(strict_types=1);

use App\Models\CustomUnit;
use App\Models\TariffClassification;

use function Pest\Laravel\assertDatabaseCount;

test('se puede crear una fraccion', function () {
    TariffClassification::factory()->create();

    assertDatabaseCount('tariff_classifications', 1);
});

test('se puede crear una fraccion y se muestra su unidad medida', function () {
    TariffClassification::factory()->create();

    $fraccion = TariffClassification::query()->with('customUnit')->first();

    expect($fraccion->customUnit)->toBeInstanceOf(CustomUnit::class);
});
