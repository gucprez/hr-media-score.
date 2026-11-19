<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('home_team_id')->constrained('tournament_teams')->restrictOnDelete();
            $table->foreignId('away_team_id')->constrained('tournament_teams')->restrictOnDelete();
            $table->string('stage', 20)->default('regular');
            $table->unsignedSmallInteger('round_number')->nullable();
            $table->string('series_key', 40)->nullable();          // agrupa juegos de una serie (Fase 2)
            $table->unsignedTinyInteger('series_game_number')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->unsignedSmallInteger('home_score')->default(0);
            $table->unsignedSmallInteger('away_score')->default(0);
            $table->unsignedTinyInteger('current_period')->nullable();
            $table->foreignId('winner_team_id')->nullable()->constrained('tournament_teams')->nullOnDelete();
            $table->foreignId('scorekeeper_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tournament_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->index('series_key');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE games ADD CONSTRAINT games_distinct_teams CHECK (home_team_id <> away_team_id)');
        }

        Schema::create('game_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('period_number');
            $table->boolean('is_overtime')->default(false);
            $table->unsignedSmallInteger('home_points')->default(0);
            $table->unsignedSmallInteger('away_points')->default(0);
            $table->timestamps();

            $table->unique(['game_id', 'period_number']);
        });

        // Bitácora inmutable de jugadas (mesa de anotación, Fase 3).
        // Las correcciones anulan (voided_at) y referencian el evento corregido.
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->unsignedTinyInteger('period_number');
            $table->unsignedSmallInteger('clock_seconds')->nullable();
            $table->foreignId('tournament_team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('related_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('type', 30);
            $table->tinyInteger('points')->default(0);
            $table->json('meta')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('corrects_event_id')->nullable()->constrained('game_events')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['game_id', 'sequence']);
            $table->index(['game_id', 'type']);
            $table->index(['player_id', 'type']);
        });

        Schema::create('player_game_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->restrictOnDelete();
            $table->foreignId('tournament_team_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_starter')->default(false);
            $table->unsignedSmallInteger('seconds_played')->default(0);
            $table->unsignedSmallInteger('points')->default(0);
            $table->unsignedTinyInteger('fg2_made')->default(0);
            $table->unsignedTinyInteger('fg2_attempted')->default(0);
            $table->unsignedTinyInteger('fg3_made')->default(0);
            $table->unsignedTinyInteger('fg3_attempted')->default(0);
            $table->unsignedTinyInteger('ft_made')->default(0);
            $table->unsignedTinyInteger('ft_attempted')->default(0);
            $table->unsignedTinyInteger('offensive_rebounds')->default(0);
            $table->unsignedTinyInteger('defensive_rebounds')->default(0);
            $table->unsignedTinyInteger('assists')->default(0);
            $table->unsignedTinyInteger('steals')->default(0);
            $table->unsignedTinyInteger('blocks')->default(0);
            $table->unsignedTinyInteger('turnovers')->default(0);
            $table->unsignedTinyInteger('personal_fouls')->default(0);
            $table->unsignedTinyInteger('technical_fouls')->default(0);
            $table->timestamps();

            $table->unique(['game_id', 'player_id']);
            $table->index(['player_id', 'tournament_team_id']);
        });

        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_team_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->nullable();
            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('won')->default(0);
            $table->unsignedSmallInteger('lost')->default(0);
            $table->unsignedSmallInteger('forfeits')->default(0);
            $table->unsignedInteger('points_for')->default(0);
            $table->unsignedInteger('points_against')->default(0);
            $table->integer('point_diff')->default(0);
            $table->unsignedSmallInteger('standing_points')->default(0);
            $table->timestamps();

            $table->unique(['tournament_id', 'tournament_team_id']);
            $table->index(['tournament_id', 'position']);
        });

        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tournament_team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->unsignedTinyInteger('games_suspended')->default(0);
            $table->decimal('fine_amount', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->date('issued_on');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tournament_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanctions');
        Schema::dropIfExists('standings');
        Schema::dropIfExists('player_game_stats');
        Schema::dropIfExists('game_events');
        Schema::dropIfExists('game_periods');
        Schema::dropIfExists('games');
    }
};
