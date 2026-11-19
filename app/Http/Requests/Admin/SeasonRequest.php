<?php

namespace App\Http\Requests\Admin;

use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeasonRequest extends FormRequest
{
    use ResolvesClub;

    public function authorize(): bool
    {
        $season = $this->route('season');

        return $season ? $this->user()->can('update', $season) : $this->user()->can('create', Season::class);
    }

    public function rules(): array
    {
        return array_merge($this->clubRules(), [
            'name' => ['required', 'string', 'max:100',
                Rule::unique('seasons')->where('club_id', $this->targetClubId())->ignore($this->route('season'))],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['boolean'],
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_current' => $this->boolean('is_current')]);
    }
}
