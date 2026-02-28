<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Producto> */
final class ProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'clave_prod_serv' => '01010101',
            'clave_unidad' => 'E48',
            'descripcion' => fake()->sentence(4),
            'precio_unitario' => fake()->randomFloat(6, 1, 99999),
            'objeto_imp_clave' => '02',
        ];
    }
}
