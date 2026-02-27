<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClaveUnidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class ClaveUnidad extends Model
{
    /** @use HasFactory<ClaveUnidadFactory> */
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $table = 'claves_unidad';

    #[Override]
    protected $primaryKey = 'clave';

    #[Override]
    protected $keyType = 'string';

    #[Override]
    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'nota',
        'vigencia_inicio',
        'vigencia_fin',
        'simbolo',
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
}
