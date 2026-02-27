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
        Schema::create('claves_prod_serv', function (Blueprint $table): void {
            $table->string('clave', 20)->primary();
            $table->string('descripcion');
            $table->string('incluye_iva', 20)->nullable();
            $table->string('incluye_ieps', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();
            $table->boolean('estimulo_franja')->default(false);
            $table->text('palabras_similares')->nullable();
            $table->timestamps();
            $table->index('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claves_prod_serv');
    }
};
