<?php

namespace Database\Seeders;

use App\Actions\RegisterTeamInTournament;
use App\Actions\SaveTournament;
use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Enums\RegistrationStatus;
use App\Enums\TournamentGender;
use App\Enums\TournamentStatus;
use App\Models\Club;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\Venue;
use Illuminate\Database\Seeder;

/**
 * Datos oficiales iniciales: Club Gustavo Behall y el XIV Torneo Juvenil
 * Infantil con Refuerzos. Idempotente: puede ejecutarse varias veces.
 *
 * Los valores hexadecimales de los colores son aproximaciones de los nombres
 * de color indicados por el club y pueden ajustarse desde el panel.
 */
class GustavoBehallSeeder extends Seeder
{
    public function run(): void
    {
        $club = Club::firstOrCreate(['slug' => 'club-gustavo-behall'], [
            'name' => 'Club Gustavo Behall',
            'short_name' => 'GB',
            'founded_year' => 1920,
            'city' => 'Puerto Plata',
            'province' => 'Puerto Plata',
            'country' => 'DO',
            'primary_color' => '#D92332',
            'is_active' => true,
        ]);

        $venue = Venue::firstOrCreate(
            ['club_id' => $club->id, 'name' => 'Techado Club Gustavo Behall'],
            ['city' => 'Puerto Plata'],
        );

        $season = Season::firstOrCreate(
            ['club_id' => $club->id, 'slug' => 'temporada-2026'],
            ['name' => 'Temporada 2026', 'is_current' => true],
        );

        $tournament = Tournament::where('club_id', $club->id)->where('edition', 'XIV')
            ->where('name', 'Torneo Juvenil Infantil con Refuerzos')->first();

        if (! $tournament) {
            $tournament = app(SaveTournament::class)->handle([
                'club_id' => $club->id,
                'season_id' => $season->id,
                'venue_id' => $venue->id,
                'name' => 'Torneo Juvenil Infantil con Refuerzos',
                'edition' => 'XIV',
                'motto' => 'Volviendo a la Tradición',
                'sponsor_name' => 'Copa La Tendencia Podcast',
                'category' => 'Juvenil Infantil',
                'gender' => TournamentGender::Male,
                'status' => TournamentStatus::Registration,
                'is_featured' => true,
                'is_public' => true,
            ], [
                'min_age' => 15,
                'max_age' => 18,
                'max_roster_size' => 10,
                'max_reinforcements' => 2,
                'reinforcements_exempt_from_age' => true,
                'min_minutes_per_player' => 16,
                'regular_season_format' => 'round_robin',
                'round_robin_legs' => 1,
                'games_per_team' => 3,
                'teams_qualifying' => 3,
                'direct_final_seeds' => 1,
                'semifinal_best_of' => 3,
                'final_best_of' => 5,
                'sanctions_policy' => 'Las faltas técnicas, expulsiones y agresiones se sancionan según el reglamento oficial del torneo. El comité organizador determina los partidos de suspensión correspondientes.',
                'punctuality_policy' => 'Los equipos deben presentarse a la hora programada con el mínimo de jugadores disponibles. El incumplimiento se resuelve según el reglamento oficial.',
                'additional_rules' => "Serie regular todos contra todos (3 partidos por equipo).\nClasifican 3 equipos: el 1.º avanza directamente a la final; el 2.º y el 3.º disputan la semifinal al mejor de 3.\nFinal al mejor de 5 partidos.\nParticipación mínima de 16 minutos por jugador.\nHasta 2 refuerzos por equipo.",
            ]);
        }

        $teams = [
            ['name' => 'Balkanes', 'short_name' => 'BAL', 'primary_color' => '#1F4FBF', 'primary_color_name' => 'Azul', 'secondary_colors' => []],
            ['name' => 'Calle Duarte', 'short_name' => 'CDU', 'primary_color' => '#FFFFFF', 'primary_color_name' => 'Blanco',
                'secondary_colors' => [['name' => 'Azul rey', 'hex' => '#1D4ED8'], ['name' => 'Azul marino', 'hex' => '#0A1F44']]],
            ['name' => 'Cacuses', 'short_name' => 'CAC', 'primary_color' => '#111111', 'primary_color_name' => 'Negro',
                'secondary_colors' => [['name' => 'Azul', 'hex' => '#1F4FBF'], ['name' => 'Blanco', 'hex' => '#FFFFFF']]],
            ['name' => 'Ninjas GB', 'short_name' => 'NGB', 'primary_color' => '#C8102E', 'primary_color_name' => 'Rojo',
                'secondary_colors' => [['name' => 'Amarillo', 'hex' => '#FFC72C'], ['name' => 'Negro', 'hex' => '#111111']]],
        ];

        $reg = [];
        foreach ($teams as $data) {
            $team = Team::firstOrCreate(['club_id' => $club->id, 'name' => $data['name']], $data + ['is_active' => true]);
            $reg[$data['name']] = $tournament->registrations()->where('team_id', $team->id)->first()
                ?? app(RegisterTeamInTournament::class)->handle($tournament, $team, ['status' => RegistrationStatus::Confirmed]);
        }

        // Datos del afiche oficial (Fase 2).
        $club->founded_year ??= 1920;
        $club->save();
        $tournament->sponsor_name ??= 'Copa La Tendencia Podcast';
        $tournament->save();

        // Jornada 1 — sábado 21 de noviembre de 2026, doble cartelera (afiche oficial).
        $jornada1 = [
            ['Ninjas GB', 'Cacuses', '2026-11-21 18:00:00'],
            ['Calle Duarte', 'Balkanes', '2026-11-21 19:00:00'],
        ];
        foreach ($jornada1 as [$home, $away, $at]) {
            Game::firstOrCreate(
                ['tournament_id' => $tournament->id, 'home_team_id' => $reg[$home]->id, 'away_team_id' => $reg[$away]->id, 'stage' => GameStage::Regular->value],
                ['club_id' => $club->id, 'venue_id' => $venue->id, 'round_number' => 1, 'scheduled_at' => $at, 'status' => GameStatus::Scheduled],
            );
        }
        if ($tournament->status === TournamentStatus::Registration) {
            $tournament->update(['status' => TournamentStatus::InProgress]);
        }
    }
}
