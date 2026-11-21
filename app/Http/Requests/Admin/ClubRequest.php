<?php

namespace App\Http\Requests\Admin;

use App\Models\Club;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        $club = $this->route('club');

        return $club ? $this->user()->can('update', $club) : $this->user()->can('create', Club::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('clubs', 'name')->ignore($this->route('club'))],
            'short_name' => ['nullable', 'string', 'max:30'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2', 'alpha'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'primary_color' => ['nullable', 'hex_color'],
            'secondary_color' => ['nullable', 'hex_color'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096', 'dimensions:max_width=5000,max_height=5000'],
            'remove_logo' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_logo' => $this->boolean('remove_logo'),
            'country' => strtoupper((string) ($this->country ?: 'DO')),
        ]);
    }
}
