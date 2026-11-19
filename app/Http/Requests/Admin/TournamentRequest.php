<?php

namespace App\Http\Requests\Admin;

use App\Enums\TournamentGender;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TournamentRequest extends FormRequest
{
    use ResolvesClub;

    public function authorize(): bool
    {
        $t = $this->route('tournament');

        return $t ? $this->user()->can('update', $t) : $this->user()->can('create', Tournament::class);
    }

    public function rules(): array
    {
        $club = $this->targetClubId();
        $ownedBy = fn (string $table) => Rule::exists($table, 'id')->where('club_id', $club);

        return array_merge($this->clubRules(), [
            'name' => ['required', 'string', 'max:180'],
            'edition' => ['nullable', 'string', 'max:20',
                Rule::unique('tournaments')->where('club_id', $club)->where('name', $this->input('name'))
                    ->whereNull('deleted_at')->ignore($this->route('tournament'))],
            'motto' => ['nullable', 'string', 'max:180'],
            'category' => ['nullable', 'string', 'max:80'],
            'gender' => ['required', Rule::enum(TournamentGender::class)],
            'status' => ['required', Rule::enum(TournamentStatus::class)],
            'season_id' => ['nullable', 'integer', $ownedBy('seasons')],
            'venue_id' => ['nullable', 'integer', $ownedBy('venues')],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_featured' => ['boolean'],
            'is_public' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096', 'dimensions:max_width=5000,max_height=5000'],
            'remove_logo' => ['boolean'],

            // Reglamento
            'rules.min_age' => ['nullable', 'integer', 'min:4', 'max:99'],
            'rules.max_age' => ['nullable', 'integer', 'min:4', 'max:99', 'gte:rules.min_age'],
            'rules.age_reference_date' => ['nullable', 'date'],
            'rules.max_roster_size' => ['required', 'integer', 'min:5', 'max:30'],
            'rules.max_reinforcements' => ['required', 'integer', 'min:0', 'lte:rules.max_roster_size'],
            'rules.reinforcements_exempt_from_age' => ['boolean'],
            'rules.reinforcement_max_age' => ['nullable', 'integer', 'min:4', 'max:99'],
            'rules.min_minutes_per_player' => ['nullable', 'integer', 'min:0', 'max:60'],
            'rules.regular_season_format' => ['required', Rule::in(['round_robin', 'groups', 'none'])],
            'rules.round_robin_legs' => ['required', 'integer', 'min:1', 'max:4'],
            'rules.games_per_team' => ['nullable', 'integer', 'min:0', 'max:100'],
            'rules.teams_qualifying' => ['nullable', 'integer', 'min:0', 'max:64'],
            'rules.direct_final_seeds' => ['required', 'integer', 'min:0', 'max:2'],
            'rules.semifinal_best_of' => ['nullable', 'integer', Rule::in([1, 3, 5, 7])],
            'rules.final_best_of' => ['nullable', 'integer', Rule::in([1, 3, 5, 7])],
            'rules.periods' => ['required', 'integer', 'min:1', 'max:4'],
            'rules.period_minutes' => ['required', 'integer', 'min:1', 'max:30'],
            'rules.overtime_minutes' => ['required', 'integer', 'min:1', 'max:15'],
            'rules.personal_foul_limit' => ['required', 'integer', 'min:1', 'max:10'],
            'rules.technical_foul_ejection' => ['required', 'integer', 'min:1', 'max:5'],
            'rules.points_per_win' => ['required', 'integer', 'min:0', 'max:5'],
            'rules.points_per_loss' => ['required', 'integer', 'min:0', 'max:5'],
            'rules.points_per_forfeit' => ['required', 'integer', 'min:0', 'max:5'],
            'rules.tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'rules.min_players_to_start' => ['required', 'integer', 'min:1', 'max:5'],
            'rules.sanctions_policy' => ['nullable', 'string', 'max:10000'],
            'rules.punctuality_policy' => ['nullable', 'string', 'max:10000'],
            'rules.additional_rules' => ['nullable', 'string', 'max:30000'],
        ]);
    }

    public function messages(): array
    {
        return ['edition.unique' => 'Ya existe un torneo con ese nombre y edición en la organización.'];
    }

    protected function prepareForValidation(): void
    {
        $rules = (array) $this->input('rules', []);
        $rules['reinforcements_exempt_from_age'] = filter_var($rules['reinforcements_exempt_from_age'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'is_public' => $this->boolean('is_public'),
            'remove_logo' => $this->boolean('remove_logo'),
            'rules' => $rules,
        ]);
    }

    public function tournamentData(): array
    {
        return collect($this->validatedForClub())->except('rules')->all();
    }

    public function rulesData(): array
    {
        return $this->validated('rules', []);
    }
}
