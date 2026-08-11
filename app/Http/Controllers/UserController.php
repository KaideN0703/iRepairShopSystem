<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->canAny(['users.manage.full', 'users.manage.limited']), 403);
        $users = User::with('roles')->latest()->get();
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canAny(['users.manage.full', 'users.manage.limited']), 403);

        // shop_manager (users.manage.limited) may only see restricted roles
        $roles = auth()->user()->can('users.manage.full')
            ? Role::all()
            : Role::whereNotIn('name', ['admin', 'shop_manager'])->get();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAny(['users.manage.full', 'users.manage.limited']), 403);

        // Prevent shop_manager from assigning privileged roles
        if (! auth()->user()->can('users.manage.full') && in_array($request->input('role'), ['admin', 'shop_manager'])) {
            abort(403, 'You are not allowed to assign this role.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:6',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->assignRole($validated['role']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'create',
            'module' => 'Users',
            'description' => "Created user {$user->email} with role {$validated['role']}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('users.index')->with('success', "User account '{$user->name}' created!");
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->canAny(['users.manage.full', 'users.manage.limited']), 403);

        // shop_manager cannot edit admins or other managers
        if (! auth()->user()->can('users.manage.full') && $user->hasAnyRole(['admin', 'shop_manager'])) {
            abort(403, 'You are not allowed to edit this user.');
        }

        $roles = auth()->user()->can('users.manage.full')
            ? Role::all()
            : Role::whereNotIn('name', ['admin', 'shop_manager'])->get();

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->canAny(['users.manage.full', 'users.manage.limited']), 403);

        // shop_manager cannot update admins/managers or reassign to privileged roles
        if (! auth()->user()->can('users.manage.full')) {
            if ($user->hasAnyRole(['admin', 'shop_manager'])) {
                abort(403, 'You are not allowed to edit this user.');
            }
            if (in_array($request->input('role'), ['admin', 'shop_manager'])) {
                abort(403, 'You are not allowed to assign this role.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$validated['role']]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'update',
            'module' => 'Users',
            'description' => "Updated user {$user->email} role to {$validated['role']}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('users.index')->with('success', "User '{$user->name}' updated successfully!");
    }
}
