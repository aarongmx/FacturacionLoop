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
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('clave_prod_serv', 10);
            $table->string('clave_unidad', 10);
            $table->string('descripcion', 1000);
            $table->decimal('precio_unitario', 12, 6);
            $table->string('objeto_imp_clave', 2);
            $table->foreign('clave_prod_serv')->references('clave')->on('claves_prod_serv');
            $table->foreign('clave_unidad')->references('clave')->on('claves_unidad');
            $table->foreign('objeto_imp_clave')->references('clave')->on('objetos_imp');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
