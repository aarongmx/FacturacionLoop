<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;
}
