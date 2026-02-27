<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FormaPagoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class FormaPago extends Model
{
    /** @use HasFactory<FormaPagoFactory> */
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $primaryKey = 'clave';

    #[Override]
    protected $keyType = 'string';

    #[Override]
    protected $fillable = [
        'clave',
        'descripcion',
        'bancarizado',
        'vigencia_inicio',
        'vigencia_fin',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'bancarizado' => 'boolean',
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }
}
