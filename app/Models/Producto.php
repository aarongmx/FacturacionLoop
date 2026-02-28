<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    use SoftDeletes;

    #[Override]
    protected $table = 'productos';

    #[Override]
    protected $fillable = [
        'clave_prod_serv',
        'clave_unidad',
        'descripcion',
        'precio_unitario',
        'objeto_imp_clave',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:6',
        ];
    }

    /** @return BelongsTo<ClaveProdServ, $this> */
    public function claveProdServ(): BelongsTo
    {
        return $this->belongsTo(ClaveProdServ::class, 'clave_prod_serv', 'clave');
    }

    /** @return BelongsTo<ClaveUnidad, $this> */
    public function claveUnidad(): BelongsTo
    {
        return $this->belongsTo(ClaveUnidad::class, 'clave_unidad', 'clave');
    }

    /** @return BelongsTo<ObjetoImp, $this> */
    public function objetoImp(): BelongsTo
    {
        return $this->belongsTo(ObjetoImp::class, 'objeto_imp_clave', 'clave');
    }

    /** @return HasMany<ProductoImpuesto, $this> */
    public function impuestos(): HasMany
    {
        return $this->hasMany(ProductoImpuesto::class, 'producto_id');
    }
}
