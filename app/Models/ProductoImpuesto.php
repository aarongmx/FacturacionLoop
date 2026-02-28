<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductoImpuestoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

final class ProductoImpuesto extends Model
{
    /** @use HasFactory<ProductoImpuestoFactory> */
    use HasFactory;

    #[Override]
    protected $table = 'producto_impuestos';

    #[Override]
    protected $fillable = [
        'producto_id',
        'impuesto_clave',
        'tipo_factor',
        'tasa_o_cuota_id',
        'es_retencion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_retencion' => 'boolean',
        ];
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return BelongsTo<Impuesto, $this> */
    public function impuesto(): BelongsTo
    {
        return $this->belongsTo(Impuesto::class, 'impuesto_clave', 'clave');
    }

    /** @return BelongsTo<TasaOCuota, $this> */
    public function tasaOCuota(): BelongsTo
    {
        return $this->belongsTo(TasaOCuota::class);
    }
}
