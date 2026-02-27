<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RegimenFiscalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class RegimenFiscal extends Model
{
    /** @use HasFactory<RegimenFiscalFactory> */
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $table = 'regimenes_fiscales';

    #[Override]
    protected $primaryKey = 'clave';

    #[Override]
    protected $keyType = 'string';

    #[Override]
    protected $fillable = [
        'clave',
        'descripcion',
        'aplica_fisica',
        'aplica_moral',
        'vigencia_inicio',
        'vigencia_fin',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'aplica_fisica' => 'boolean',
            'aplica_moral' => 'boolean',
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }
}
