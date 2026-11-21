<?php

namespace App\Http\Requests\Admin;

use App\Enums\GameStage;
use App\Enums\Role;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Programación de un partido (equipos, fecha, sede, jornada, anotador). */
class GameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $game = $this->route('game');

        return $game ? $this->user()->can('update', $game) : $this->user()->can('create', Game::class)
            && $this->user()->belongsToClub($this->route('tournament')->club_id);
    }

    public function rules(): array
    {
        $tournament = $this->route('tournament');
        $team = Rule::exists('tournament_teams', 'id')->where('tournament_id', $tournament->id);

        return [
            'home_team_id' => ['required', 'integer', $team],
            'away_team_id' => ['required', 'integer', $team, 'different:home_team_id'],
            'stage' => ['required', Rule::enum(GameStage::class)],
            'round_number' => ['nullable', 'integer', 'min:1', 'max:200'],
            'date' => ['nullable', 'date', 'required_with:time'],
            'time' => ['nullable', 'date_format:H:i'],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')->where('club_id', $tournament->club_id)],
            'scorekeeper_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('club_id', $tournament->club_id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['away_team_id.different' => 'Un equipo no puede jugar contra sí mismo.'];
    }

    public function after(): array
    {
        return [function ($validator) {
            $id = $this->input('scorekeeper_id');
            if ($id && ! User::find($id)?->hasAnyRole([Role::Scorekeeper->value, Role::Organizer->value, Role::ClubAdmin->value])) {
                $validator->errors()->add('scorekeeper_id', 'El usuario seleccionado no puede anotar partidos.');
            }
        }];
    }

    public function gameData(): array
    {
        $data = $this->safe()->except(['date', 'time']);
        $data['scheduled_at'] = $this->filled('date') ? $this->input('date').' '.($this->input('time') ?: '00:00') : null;

        return $data;
    }
}
