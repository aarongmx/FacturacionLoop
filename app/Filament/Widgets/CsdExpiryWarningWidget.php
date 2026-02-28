<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Csd;
use Filament\Widgets\Widget;

final class CsdExpiryWarningWidget extends Widget
{
    public ?Csd $expiringCsd = null;

    public ?int $daysRemaining = null;

    protected string $view = 'filament.widgets.csd-expiry-warning';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return Csd::query()->whereExpiring()->exists();
    }

    public function mount(): void
    {
        $this->expiringCsd = Csd::query()->whereExpiring()->first();

        if ($this->expiringCsd !== null) {
            $this->daysRemaining = (int) now()->diffInDays($this->expiringCsd->fecha_fin, absolute: false);
        }
    }
}
