<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 180);
            $table->string('slug', 200);
            $table->string('edition', 20)->nullable();      // ej. "XIV"
            $table->string('motto', 180)->nullable();       // lema
            $table->string('category', 80)->nullable();     // ej. "Juvenil Infantil"
            $table->string('gender', 20)->default('male');
            $table->string('status', 20)->default('draft')->index();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['club_id', 'slug']);
            $table->index(['is_public', 'is_featured']);
        });

        // Reglamento configurable por torneo (1:1). Se separa de `tournaments`
        // para que cada campeonato tenga sus propias reglas sin constantes en código.
        Schema::create('tournament_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->unique()->constrained()->cascadeOnDelete();
            // Elegibilidad
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->unsignedTinyInteger('max_age')->nullable();
            $table->date('age_reference_date')->nullable(); // fecha de corte de edad
            $table->unsignedTinyInteger('max_roster_size')->default(12);
            $table->unsignedTinyInteger('max_reinforcements')->default(0);
            $table->boolean('reinforcements_exempt_from_age')->default(true);
            $table->unsignedTinyInteger('reinforcement_max_age')->nullable();
            $table->unsignedSmallInteger('min_minutes_per_player')->nullable();
            // Formato de competición
            $table->string('regular_season_format', 30)->default('round_robin');
            $table->unsignedTinyInteger('round_robin_legs')->default(1);
            $table->unsignedTinyInteger('games_per_team')->nullable();
            $table->unsignedTinyInteger('teams_qualifying')->nullable();
            $table->unsignedTinyInteger('direct_final_seeds')->default(0);
            $table->unsignedTinyInteger('semifinal_best_of')->nullable();
            $table->unsignedTinyInteger('final_best_of')->nullable();
            // Juego
            $table->unsignedTinyInteger('periods')->default(4);
            $table->unsignedTinyInteger('period_minutes')->default(10);
            $table->unsignedTinyInteger('overtime_minutes')->default(5);
            $table->unsignedTinyInteger('personal_foul_limit')->default(5);
            $table->unsignedTinyInteger('technical_foul_ejection')->default(2);
            // Puntuación en tabla
            $table->unsignedTinyInteger('points_per_win')->default(2);
            $table->unsignedTinyInteger('points_per_loss')->default(1);
            $table->unsignedTinyInteger('points_per_forfeit')->default(0);
            $table->json('tiebreakers')->nullable();
            // Disciplina y puntualidad
            $table->unsignedSmallInteger('tolerance_minutes')->nullable();
            $table->unsignedTinyInteger('min_players_to_start')->default(5);
            $table->text('sanctions_policy')->nullable();
            $table->text('punctuality_policy')->nullable();
            $table->longText('additional_rules')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_rules');
        Schema::dropIfExists('tournaments');
    }
};
