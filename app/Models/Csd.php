<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\CsdBuilder;
use App\Enums\CsdStatus;
use Database\Factories\CsdFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[UseEloquentBuilder(CsdBuilder::class)]
final class Csd extends Model
{
    /** @use HasFactory<CsdFactory> */
    use HasFactory;

    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'no_certificado',
        'rfc',
        'fecha_inicio',
        'fecha_fin',
        'status',
        'key_path',
        'passphrase_encrypted',
        'cer_path',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'status' => CsdStatus::class,
            'passphrase_encrypted' => 'encrypted',
        ];
    }
}
