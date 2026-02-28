<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class UploadCsdData extends Data
{
    public function __construct(
        public string $cerFilePath,
        public string $keyFilePath,
        public string $passphrase,
    ) {}
}
