<?php

namespace App\Actions;

use App\Models\Tournament;
use App\Models\TournamentRule;
use Illuminate\Support\Facades\DB;

/** Crea o actualiza un torneo junto con su reglamento, de forma atómica. */
class SaveTournament
{
    public function handle(array $data, array $rules, ?Tournament $tournament = null): Tournament
    {
        return DB::transaction(function () use ($data, $rules, $tournament) {
            $tournament ??= new Tournament;
            $tournament->fill($data)->save();

            $tournament->rules()->updateOrCreate(
                ['tournament_id' => $tournament->id],
                $tournament->wasRecentlyCreated ? array_merge(TournamentRule::defaults(), $rules) : $rules,
            );

            if ($tournament->is_featured) {
                // Solo un torneo destacado por organización.
                Tournament::where('club_id', $tournament->club_id)->whereKeyNot($tournament->id)
                    ->where('is_featured', true)->update(['is_featured' => false]);
            }

            return $tournament->load('rules');
        });
    }
}
