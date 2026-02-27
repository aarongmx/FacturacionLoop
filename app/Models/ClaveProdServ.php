<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClaveProdServFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class ClaveProdServ extends Model
{
    /** @use HasFactory<ClaveProdServFactory> */
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $table = 'claves_prod_serv';

    #[Override]
    protected $primaryKey = 'clave';

    #[Override]
    protected $keyType = 'string';

    #[Override]
    protected $fillable = [
        'clave',
        'descripcion',
        'incluye_iva',
        'incluye_ieps',
        'complemento',
        'vigencia_inicio',
        'vigencia_fin',
        'estimulo_franja',
        'palabras_similares',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'estimulo_franja' => 'boolean',
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }
}
