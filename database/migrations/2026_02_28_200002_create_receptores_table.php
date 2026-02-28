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
        Schema::create('receptores', function (Blueprint $table): void {
            $table->id();
            $table->string('rfc', 13)->index();
            $table->string('nombre_fiscal', 300);
            $table->string('domicilio_fiscal_cp', 5);
            $table->string('regimen_fiscal_clave', 10)->nullable();
            $table->string('uso_cfdi_clave', 10)->nullable();
            $table->foreign('regimen_fiscal_clave')->references('clave')->on('regimenes_fiscales');
            $table->foreign('uso_cfdi_clave')->references('clave')->on('usos_cfdi');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receptores');
    }
};
