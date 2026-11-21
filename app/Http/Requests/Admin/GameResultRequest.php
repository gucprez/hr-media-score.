<?php

namespace App\Http\Requests\Admin;

use App\Enums\GameStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordResult', $this->route('game'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(GameStatus::class)],
            'mode' => ['nullable', Rule::in(['periods', 'total'])],
            'home_score' => ['nullable', 'integer', 'min:0', 'max:300'],
            'away_score' => ['nullable', 'integer', 'min:0', 'max:300'],
            'periods' => ['nullable', 'array', 'max:10'],
            'periods.*.home' => ['nullable', 'integer', 'min:0', 'max:150'],
            'periods.*.away' => ['nullable', 'integer', 'min:0', 'max:150'],
            'forfeit_loser' => ['nullable', Rule::in(['home', 'away'])],
        ];
    }

    public function resultData(): array
    {
        $data = $this->validated();
        if (($data['mode'] ?? 'total') === 'total') {
            unset($data['periods']);
        }

        return $data;
    }
}
