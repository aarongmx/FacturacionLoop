<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('producto_impuestos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('impuesto_clave', 3);
            $table->string('tipo_factor', 10);
            $table->foreignId('tasa_o_cuota_id')->constrained('tasas_o_cuotas');
            $table->boolean('es_retencion')->default(false);
            $table->timestamps();
            $table->foreign('impuesto_clave')->references('clave')->on('impuestos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_impuestos');
    }
};
