<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TipoDeComprobanteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class TipoDeComprobante extends Model
{
    /** @use HasFactory<TipoDeComprobanteFactory> */
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $primaryKey = 'clave';

    #[Override]
    protected $keyType = 'string';

    #[Override]
    protected $table = 'tipos_comprobante';

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
    protected function casts(): array
    {
        return [
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }
}
