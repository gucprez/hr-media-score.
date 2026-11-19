<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user ? $this->user()->can('update', $user) : $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $actor = $this->user();
        $roles = $actor->isSuperAdmin()
            ? Role::values()
            : array_map(fn (Role $r) => $r->value, Role::assignableByClubAdmin());
        $editing = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($editing)],
            'password' => [$editing ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in($roles)],
            'club_id' => $actor->isSuperAdmin()
                ? [Rule::requiredIf($this->input('role') !== Role::SuperAdmin->value), 'nullable', 'integer', Rule::exists('clubs', 'id')]
                : ['prohibited'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active'), 'email' => mb_strtolower(trim((string) $this->email))]);
    }

    public function userData(): array
    {
        $data = $this->safe()->only(['name', 'email', 'is_active']);
        if ($this->filled('password')) {
            $data['password'] = $this->validated('password');
        }
        $data['club_id'] = $this->user()->isSuperAdmin()
            ? ($this->validated('role') === Role::SuperAdmin->value ? null : $this->validated('club_id'))
            : $this->user()->club_id;

        return $data;
    }
}
