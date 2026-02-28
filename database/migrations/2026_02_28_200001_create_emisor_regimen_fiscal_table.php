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
        Schema::create('emisor_regimen_fiscal', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('emisor_id')->constrained('emisores')->cascadeOnDelete();
            $table->string('regimen_fiscal_clave', 10);
            $table->foreign('regimen_fiscal_clave')->references('clave')->on('regimenes_fiscales');
            $table->unique(['emisor_id', 'regimen_fiscal_clave']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emisor_regimen_fiscal');
    }
};
