<?php

namespace App\Http\Requests\Admin;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VenueRequest extends FormRequest
{
    use ResolvesClub;

    public function authorize(): bool
    {
        $venue = $this->route('venue');

        return $venue ? $this->user()->can('update', $venue) : $this->user()->can('create', Venue::class);
    }

    public function rules(): array
    {
        return array_merge($this->clubRules(), [
            'name' => ['required', 'string', 'max:150',
                Rule::unique('venues')->where('club_id', $this->targetClubId())->ignore($this->route('venue'))],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
    }
}
