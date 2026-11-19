<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Franquicia/equipo permanente: sobrevive a torneos y temporadas.
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->string('short_name', 12)->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->json('secondary_colors')->nullable();
            $table->string('primary_color_name', 40)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('coach_name', 120)->nullable();
            $table->string('assistant_coach_name', 120)->nullable();
            $table->string('delegate_name', 120)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['club_id', 'slug']);
            $table->unique(['club_id', 'name']);
        });

        // Persona-jugador: independiente de cualquier equipo para conservar historial.
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('slug', 180);
            $table->string('nickname', 60)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('document_number', 30)->nullable(); // cédula / pasaporte
            $table->string('gender', 10)->nullable();
            $table->string('position', 4)->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            $table->string('dominant_hand', 5)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('guardian_name', 120)->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['club_id', 'slug']);
            $table->unique(['club_id', 'document_number']);
            // Evita registrar dos veces a la misma persona.
            $table->unique(['club_id', 'first_name', 'last_name', 'birth_date'], 'players_identity_unique');
            $table->index(['club_id', 'last_name']);
        });

        // Inscripción de un equipo en un torneo.
        Schema::create('tournament_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->restrictOnDelete();
            $table->string('display_name', 120)->nullable();
            $table->string('coach_name', 120)->nullable();
            $table->string('group_name', 20)->nullable();
            $table->unsignedTinyInteger('seed')->nullable();
            $table->string('status', 20)->default('registered');
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'team_id']);
        });

        // Plantilla: jugador inscrito con un equipo en un torneo.
        Schema::create('team_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_team_id')->constrained()->cascadeOnDelete();
            // Desnormalizado para garantizar en BD: un jugador, un equipo por torneo.
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('jersey_number');
            $table->string('position', 4)->nullable();
            $table->boolean('is_reinforcement')->default(false);
            $table->boolean('is_captain')->default(false);
            $table->string('status', 20)->default('active');
            $table->date('joined_on')->nullable();
            $table->date('left_on')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'player_id']);
            $table->unique(['tournament_team_id', 'jersey_number']);
            $table->index(['player_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_rosters');
        Schema::dropIfExists('tournament_teams');
        Schema::dropIfExists('players');
        Schema::dropIfExists('teams');
    }
};
