<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 — Competiciones: series de playoffs, copa/patrocinador del torneo
 * y año de fundación del club (usado en las carteleras).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Serie de playoffs (semifinal, final…) al mejor de N partidos.
        Schema::create('playoff_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 20);                  // semifinal, final…
            $table->string('slot', 10);                   // SF1, SF2, F
            $table->unsignedTinyInteger('best_of');
            $table->foreignId('high_seed_team_id')->nullable()->constrained('tournament_teams')->nullOnDelete();
            $table->foreignId('low_seed_team_id')->nullable()->constrained('tournament_teams')->nullOnDelete();
            $table->unsignedTinyInteger('high_seed_position')->nullable();
            $table->unsignedTinyInteger('low_seed_position')->nullable();
            $table->unsignedTinyInteger('high_wins')->default(0);
            $table->unsignedTinyInteger('low_wins')->default(0);
            $table->foreignId('winner_team_id')->nullable()->constrained('tournament_teams')->nullOnDelete();
            // El ganador avanza a esta serie, en la posición indicada (high|low).
            $table->foreignId('next_series_id')->nullable()->constrained('playoff_series')->nullOnDelete();
            $table->string('next_slot', 4)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['tournament_id', 'slot']);
        });

        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('series_key')->constrained('playoff_series')->nullOnDelete();
            $table->foreignId('forfeit_loser_team_id')->nullable()->after('winner_team_id')->constrained('tournament_teams')->nullOnDelete();
            $table->index(['tournament_id', 'stage', 'round_number']);
        });

        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('sponsor_name', 120)->nullable()->after('motto');
            $table->string('sponsor_logo_path')->nullable()->after('sponsor_name');
        });

        Schema::table('clubs', function (Blueprint $table) {
            $table->unsignedSmallInteger('founded_year')->nullable()->after('short_name');
        });
    }

    public function down(): void
    {
        Schema::table('clubs', fn (Blueprint $t) => $t->dropColumn('founded_year'));
        Schema::table('tournaments', fn (Blueprint $t) => $t->dropColumn(['sponsor_name', 'sponsor_logo_path']));
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['tournament_id', 'stage', 'round_number']);
            $table->dropConstrainedForeignId('series_id');
            $table->dropConstrainedForeignId('forfeit_loser_team_id');
        });
        Schema::dropIfExists('playoff_series');
    }
};
