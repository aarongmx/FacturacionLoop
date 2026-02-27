<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\IncotermFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Incoterm extends Model
{
    /** @use HasFactory<IncotermFactory> */
    use HasFactory;
}
