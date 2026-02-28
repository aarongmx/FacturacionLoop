<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CsdStatus;
use App\Models\Csd;

final class DeactivateCsdAction
{
    public function __invoke(Csd $csd): Csd
    {
        $csd->update(['status' => CsdStatus::Inactive]);

        return $csd->refresh();
    }
}
