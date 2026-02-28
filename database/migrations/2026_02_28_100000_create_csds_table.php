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
        Schema::create('csds', function (Blueprint $table): void {
            $table->id();
            $table->string('no_certificado', 40)->unique();
            $table->string('rfc', 13);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('status', 20)->default('inactive');
            $table->string('key_path', 500);
            $table->text('passphrase_encrypted');
            $table->string('cer_path', 500);
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('fecha_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csds');
    }
};
