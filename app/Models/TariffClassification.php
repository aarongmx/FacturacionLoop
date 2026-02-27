<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TariffClassificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

final class TariffClassification extends Model
{
    /** @use HasFactory<TariffClassificationFactory> */
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
        'custom_unit_code',
    ];

    /**
     * @return BelongsTo<CustomUnit, $this>
     */
    public function customUnit(): BelongsTo
    {
        return $this->belongsTo(CustomUnit::class, 'custom_unit_code', 'code');
    }
}
