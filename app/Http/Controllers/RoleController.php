<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index()
    {
        return view('roles.index', [
            'roles'       => Role::with('permissions')->orderBy('id')->get(),
            'permissions' => Permission::orderBy('group')->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions'   => ['array'],
            'permissions.*' => ['integer','exists:permissions,id'],
        ]);

        $ids  = collect($data['permissions'] ?? []);
        $keys = Permission::whereIn('id', $ids)->pluck('key');

        // Lockout guard: an administrator cannot strip roles.manage from the
        // role they themselves hold. Enforced here, in the application tier,
        // so no interface change or crafted request can bypass it.
       if ((int) $request->user()->role_id === (int) $role->id && ! $keys->contains('roles.manage')) {
            $message = 'You cannot remove roles.manage from your own role.';

            $this->audit->denied(
                'roles.update',
                $role->name.': roles.manage (self-lockout blocked)'
            );

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['permissions' => $message]);
        }

        $before = $role->permissions->pluck('key');

        DB::transaction(fn () => $role->permissions()->sync($ids));

        $granted = $keys->diff($before)->implode(', ');
        $revoked = $before->diff($keys)->implode(', ');

        $this->audit->allowed('roles.update', $role->name, array_filter([
            'granted' => $granted ?: null,
            'revoked' => $revoked ?: null,
        ]));

        return back()->with('status', "Permissions updated for {$role->name}.");
    }
}
