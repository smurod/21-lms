<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->assignDefaultUserRoleToUsersWithoutRoles();

        $query = User::withTrashed()->with(['roles'])->latest();

        if ($search = $request->input('search')) {
            $needle = '%' . $search . '%';

            $query->where(function ($q) use ($needle) {
                // LOWER(... ) LIKE LOWER(?) works in both MySQL and PostgreSQL.
                // PostgreSQL-only ILIKE caused the admin user search to fail on MySQL.
                $q->whereRaw('LOWER(name) LIKE LOWER(?)', [$needle])
                    ->orWhereRaw('LOWER(email) LIKE LOWER(?)', [$needle]);
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->paginate(15);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function updateRole(Request $request, User $user)
    {
        if ($request->user()?->is($user)) {
            return back()->with('error', 'Вы не можете изменить собственную роль.');
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        $user->syncRoles([$validated['role']]);

        return back()->with('success', "Роль пользователя '{$user->name}' обновлена.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()?->is($user)) {
            return back()->with('error', 'Вы не можете удалить собственный аккаунт.');
        }

        $user->delete();

        return back()->with('success', "Пользователь '{$user->name}' удалён. Его можно восстановить из списка пользователей.");
    }

    public function restore(Request $request, int $user)
    {
        $restoredUser = User::withTrashed()->findOrFail($user);

        if (! $restoredUser->trashed()) {
            return back()->with('error', "Пользователь '{$restoredUser->name}' не удалён.");
        }

        $restoredUser->restore();

        if (! $restoredUser->hasAnyRole(['admin', 'user'])) {
            $restoredUser->assignRole('user');
        }

        return back()->with('success', "Пользователь '{$restoredUser->name}' восстановлен.");
    }

    private function assignDefaultUserRoleToUsersWithoutRoles(): void
    {
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        User::whereDoesntHave('roles')
            ->get()
            ->each(fn (User $user) => $user->assignRole($userRole));
    }
}
