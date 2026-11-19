<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlayerPosition;
use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerRequest extends FormRequest
{
    use ResolvesClub;

    public function authorize(): bool
    {
        $player = $this->route('player');

        return $player ? $this->user()->can('update', $player) : $this->user()->can('create', Player::class);
    }

    public function rules(): array
    {
        $club = $this->targetClubId();

        return array_merge($this->clubRules(), [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'nickname' => ['nullable', 'string', 'max:60'],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1930-01-01',
                Rule::unique('players')->where('club_id', $club)
                    ->where('first_name', $this->input('first_name'))
                    ->where('last_name', $this->input('last_name'))
                    ->ignore($this->route('player'))],
            'document_number' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('players')->where('club_id', $club)->ignore($this->route('player'))],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'position' => ['nullable', Rule::enum(PlayerPosition::class)],
            'height_cm' => ['nullable', 'integer', 'min:100', 'max:250'],
            'weight_kg' => ['nullable', 'integer', 'min:25', 'max:200'],
            'dominant_hand' => ['nullable', Rule::in(['right', 'left'])],
            'guardian_name' => ['nullable', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'remove_photo' => ['boolean'],
        ]);
    }

    public function messages(): array
    {
        return [
            'birth_date.unique' => 'Ya existe un jugador con el mismo nombre y fecha de nacimiento.',
            'document_number.unique' => 'Ya existe un jugador con ese documento de identidad.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->first_name ? trim(preg_replace('/\s+/', ' ', $this->first_name)) : null,
            'last_name' => $this->last_name ? trim(preg_replace('/\s+/', ' ', $this->last_name)) : null,
            'document_number' => $this->document_number ? strtoupper(str_replace(' ', '', $this->document_number)) : null,
            'is_active' => $this->boolean('is_active'),
            'remove_photo' => $this->boolean('remove_photo'),
        ]);
    }
}
