<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Csd;
use Filament\Widgets\Widget;
use Override;

final class CsdExpiryWarningWidget extends Widget
{
    public ?Csd $expiringCsd = null;

    public ?int $daysRemaining = null;

    #[Override]
    protected string $view = 'filament.widgets.csd-expiry-warning';

    #[Override]
    protected int|string|array $columnSpan = 'full';

    #[Override]
    protected static ?int $sort = -1;

    #[Override]
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return Csd::query()->whereExpiring()->exists();
    }

    public function mount(): void
    {
        $this->expiringCsd = Csd::query()->whereExpiring()->first();

        if ($this->expiringCsd instanceof Csd) {
            $this->daysRemaining = (int) now()->diffInDays($this->expiringCsd->fecha_fin, absolute: false);
        }
    }
}
