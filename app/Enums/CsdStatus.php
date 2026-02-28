<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CsdStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case Inactive = 'inactive';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::ExpiringSoon => 'warning',
            self::Expired => 'danger',
            self::Inactive => 'gray',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::ExpiringSoon => 'Por vencer',
            self::Expired => 'Expirado',
            self::Inactive => 'Inactivo',
        };
    }
}
