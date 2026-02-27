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
        Schema::create('claves_unidad', function (Blueprint $table): void {
            $table->string('clave', 10)->primary();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->text('nota')->nullable();
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();
            $table->string('simbolo', 50)->nullable();
            $table->timestamps();
            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claves_unidad');
    }
};
