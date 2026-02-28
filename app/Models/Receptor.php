<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReceptorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class Receptor extends Model
{
    /** @use HasFactory<ReceptorFactory> */
    use HasFactory;

    use SoftDeletes;

    #[Override]
    protected $table = 'receptores';

    #[Override]
    protected $fillable = [
        'rfc',
        'nombre_fiscal',
        'domicilio_fiscal_cp',
        'regimen_fiscal_clave',
        'uso_cfdi_clave',
    ];

    /** @return BelongsTo<RegimenFiscal, $this> */
    public function regimenFiscal(): BelongsTo
    {
        return $this->belongsTo(RegimenFiscal::class, 'regimen_fiscal_clave', 'clave');
    }

    /** @return BelongsTo<UsoCfdi, $this> */
    public function usoCfdi(): BelongsTo
    {
        return $this->belongsTo(UsoCfdi::class, 'uso_cfdi_clave', 'clave');
    }
}
