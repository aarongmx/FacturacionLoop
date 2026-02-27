<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TipoFactorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class TipoFactor extends Model
{
    /** @use HasFactory<TipoFactorFactory> */
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $table = 'tipos_factor';

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
}
