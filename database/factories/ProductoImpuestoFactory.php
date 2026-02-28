<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Producto;
use App\Models\ProductoImpuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductoImpuesto> */
final class ProductoImpuestoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'impuesto_clave' => '002',
            'tipo_factor' => 'Tasa',
            'tasa_o_cuota_id' => 1,
            'es_retencion' => false,
        ];
    }

    /** Factory state for IVA 16% traslado. */
    public function iva16(): static
    {
        return $this->state(fn (array $attributes): array => [
            'impuesto_clave' => '002',
            'tipo_factor' => 'Tasa',
            'es_retencion' => false,
        ]);
    }

    /** Factory state for ISR retencion. */
    public function isrRetencion(): static
    {
        return $this->state(fn (array $attributes): array => [
            'impuesto_clave' => '001',
            'tipo_factor' => 'Tasa',
            'es_retencion' => true,
        ]);
    }
}
