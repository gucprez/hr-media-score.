<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();

            $table->unique(['club_id', 'name']);
        });

        // Temporada = período deportivo de una organización (ej. "Temporada 2026").
        // Un torneo pertenece a una temporada; las estadísticas se agregan por temporada.
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['club_id', 'slug']);
            $table->index(['club_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
        Schema::dropIfExists('venues');
    }
};
