<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Csd;
use RuntimeException;

final class ValidateCsdExpiryAction
{
    /**
     * Returns the active CSD if valid.
     *
     * @throws RuntimeException When no active CSD exists or the active CSD is expired
     */
    public function __invoke(): Csd
    {
        $activeCsd = Csd::query()->whereActive()->first();

        if ($activeCsd === null) {
            throw new RuntimeException(
                'No hay CSD activo. Configure un certificado antes de timbrar.',
            );
        }

        if ($activeCsd->fecha_fin->isPast()) {
            throw new RuntimeException(
                'El CSD está expirado. Suba un nuevo certificado antes de timbrar.',
            );
        }

        return $activeCsd;
    }
}
