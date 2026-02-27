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
        Schema::create('tariff_classifications', function (Blueprint $table): void {
            $table->string('code', 50)->primary();
            $table->string('name');
            $table->string('custom_unit_code', 3);
            $table->timestamps();

            $table->foreign('custom_unit_code')->references('code')->on('custom_units')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariff_classifications');
    }
};
