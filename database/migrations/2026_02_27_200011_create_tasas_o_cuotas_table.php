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
        Schema::create('tasas_o_cuotas', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo', 10)->nullable();
            $table->string('valor_minimo', 20);
            $table->string('valor_maximo', 20);
            $table->string('impuesto', 5);
            $table->string('factor', 10);
            $table->boolean('traslado')->default(false);
            $table->boolean('retencion')->default(false);
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();
            $table->timestamps();
            $table->unique(
                ['impuesto', 'factor', 'valor_minimo', 'valor_maximo', 'traslado', 'retencion'],
                'tasas_o_cuotas_composite_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasas_o_cuotas');
    }
};
