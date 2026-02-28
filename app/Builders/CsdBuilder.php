<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\CsdStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\Csd
 *
 * @extends Builder<TModelClass>
 */
final class CsdBuilder extends Builder
{
    public function whereActive(): static
    {
        return $this->where('status', CsdStatus::Active);
    }

    public function whereExpiring(int $withinDays = 90): static
    {
        return $this->where(function (self $query) use ($withinDays): void {
            $query->where('status', CsdStatus::ExpiringSoon)
                ->orWhere(function (self $inner) use ($withinDays): void {
                    $inner->where('status', CsdStatus::Active)
                        ->whereBetween('fecha_fin', [now(), now()->addDays($withinDays)]);
                });
        });
    }

    public function whereNotExpired(): static
    {
        return $this->where('fecha_fin', '>', now());
    }
}
