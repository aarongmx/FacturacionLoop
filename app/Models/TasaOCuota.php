<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TasaOCuotaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

final class TasaOCuota extends Model
{
    /** @use HasFactory<TasaOCuotaFactory> */
    use HasFactory;

    #[Override]
    protected $table = 'tasas_o_cuotas';

    #[Override]
    protected $fillable = [
        'tipo',
        'valor_minimo',
        'valor_maximo',
        'impuesto',
        'factor',
        'traslado',
        'retencion',
        'vigencia_inicio',
        'vigencia_fin',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'traslado' => 'boolean',
            'retencion' => 'boolean',
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Impuesto, $this>
     */
    public function impuesto(): BelongsTo
    {
        return $this->belongsTo(Impuesto::class, 'impuesto', 'clave');
    }
}
