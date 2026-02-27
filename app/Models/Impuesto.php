<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ImpuestoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

final class Impuesto extends Model
{
    /** @use HasFactory<ImpuestoFactory> */
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $table = 'impuestos';

    #[Override]
    protected $primaryKey = 'clave';

    #[Override]
    protected $keyType = 'string';

    #[Override]
    protected $fillable = [
        'clave',
        'descripcion',
        'vigencia_inicio',
        'vigencia_fin',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }

    /**
     * @return HasMany<TasaOCuota, $this>
     */
    public function tasasOCuotas(): HasMany
    {
        return $this->hasMany(TasaOCuota::class, 'impuesto', 'clave');
    }
}
