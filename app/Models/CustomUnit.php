<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CustomUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

final class CustomUnit extends Model
{
    /** @use HasFactory<CustomUnitFactory> */
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $primaryKey = 'code';

    #[Override]
    protected $keyType = 'string';

    #[Override]
    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * @return HasMany<TariffClassification, $this>
     */
    public function tariffClassifications(): HasMany
    {
        return $this->hasMany(TariffClassification::class, 'custom_unit_code', 'code');
    }
}
