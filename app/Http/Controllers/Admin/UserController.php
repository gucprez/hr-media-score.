<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        $actor = $request->user();

        $users = User::with(['club', 'roles'])
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('club_id', $actor->club_id))
            ->when($request->string('q')->trim()->value(), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', $this->formData($request, new User(['is_active' => true])));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create($request->userData());
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->syncRoles([$request->validated('role')]);
        });

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.form', $this->formData($request, $user));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user) && (! $request->boolean('is_active') || $request->validated('role') !== $user->roles->first()?->name)) {
            return back()->withInput()->with('error', 'No puede desactivar su propia cuenta ni cambiar su propio rol.');
        }

        DB::transaction(function () use ($request, $user) {
            $user->update($request->userData());
            $user->syncRoles([$request->validated('role')]);
        });

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        // Gate::before concede todo al superadministrador; esta regla aplica a cualquier rol.
        abort_if($request->user()->is($user), 403, 'No puede eliminar su propia cuenta.');
        if ($user->isSuperAdmin() && User::role(Role::SuperAdmin->value)->count() <= 1) {
            return back()->with('error', 'No se puede eliminar al único superadministrador.');
        }
        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    private function formData(Request $request, User $user): array
    {
        $actor = $request->user();

        return [
            'user' => $user,
            'roles' => $actor->isSuperAdmin() ? Role::cases() : Role::assignableByClubAdmin(),
            'clubs' => $actor->isSuperAdmin() ? Club::orderBy('name')->get() : collect(),
        ];
    }
}
