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
        Schema::create('usos_cfdi', function (Blueprint $table): void {
            $table->string('clave', 10)->primary();
            $table->string('descripcion');
            $table->boolean('aplica_fisica')->default(false);
            $table->boolean('aplica_moral')->default(false);
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usos_cfdi');
    }
};
