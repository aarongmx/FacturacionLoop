<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CsdStatus;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class CsdData extends Data
{
    public function __construct(
        public int $id,
        public string $noCertificado,
        public string $rfc,
        public CarbonInterface $fechaInicio,
        public CarbonInterface $fechaFin,
        public CsdStatus $status,
    ) {}
}
