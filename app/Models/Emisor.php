<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmisorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

final class Emisor extends Model
{
    /** @use HasFactory<EmisorFactory> */
    use HasFactory;

    #[Override]
    protected $table = 'emisores';

    #[Override]
    protected $fillable = [
        'rfc',
        'razon_social',
        'domicilio_fiscal_cp',
        'logo_path',
    ];

    /** @return BelongsToMany<RegimenFiscal, $this> */
    public function regimenesFiscales(): BelongsToMany
    {
        return $this->belongsToMany(
            RegimenFiscal::class,
            'emisor_regimen_fiscal',
            'emisor_id',
            'regimen_fiscal_clave',
            'id',
            'clave',
        );
    }
}
