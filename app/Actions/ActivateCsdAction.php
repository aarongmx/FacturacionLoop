<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CsdStatus;
use App\Models\Csd;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ActivateCsdAction
{
    /**
     * @throws RuntimeException When the CSD is expired
     */
    public function __invoke(Csd $csd): Csd
    {
        if ($csd->fecha_fin->isPast()) {
            throw new RuntimeException(
                'No se puede activar un CSD expirado. Suba un nuevo certificado.',
            );
        }

        DB::transaction(function () use ($csd): void {
            // Deactivate the current active CSD (if any)
            Csd::query()
                ->whereActive()
                ->where('id', '!=', $csd->id)
                ->update(['status' => CsdStatus::Inactive]);

            // Activate the given CSD
            $csd->update(['status' => CsdStatus::Active]);
        });

        return $csd->refresh();
    }
}
