<?php

namespace App\Http\Requests\Admin;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
{
    use ResolvesClub;

    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team ? $this->user()->can('update', $team) : $this->user()->can('create', Team::class);
    }

    public function rules(): array
    {
        return array_merge($this->clubRules(), [
            'name' => ['required', 'string', 'max:120',
                Rule::unique('teams')->where('club_id', $this->targetClubId())->ignore($this->route('team'))],
            'short_name' => ['nullable', 'string', 'max:12', 'alpha_num'],
            'primary_color' => ['nullable', 'hex_color'],
            'primary_color_name' => ['nullable', 'string', 'max:40'],
            'secondary_colors' => ['nullable', 'array', 'max:4'],
            'secondary_colors.*.name' => ['nullable', 'string', 'max:40'],
            'secondary_colors.*.hex' => ['nullable', 'hex_color'],
            'coach_name' => ['nullable', 'string', 'max:120'],
            'assistant_coach_name' => ['nullable', 'string', 'max:120'],
            'delegate_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096', 'dimensions:max_width=5000,max_height=5000'],
            'remove_logo' => ['boolean'],
        ]);
    }

    public function messages(): array
    {
        return ['name.unique' => 'Ya existe un equipo con ese nombre en la organización.'];
    }

    protected function prepareForValidation(): void
    {
        $colors = collect((array) $this->input('secondary_colors', []))
            ->filter(fn ($c) => is_array($c) && (filled($c['name'] ?? null) || filled($c['hex'] ?? null)))
            ->values()->all();
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_logo' => $this->boolean('remove_logo'),
            'secondary_colors' => $colors,
            'short_name' => $this->short_name ? strtoupper($this->short_name) : null,
        ]);
    }
}
