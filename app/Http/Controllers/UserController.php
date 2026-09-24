<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Role;
use App\Models\Sector;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index()
    {
        return view('users.index', [
            'users'   => User::with(['role:id,name','sector:id,name'])->orderBy('name')->paginate(25),
            'roles'   => Role::orderBy('id')->get(),
            'sectors' => Sector::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = new User($request->safe()->except('phone','password'));
        $user->password = $request->input('password');        // hashed cast
        $user->phone    = $request->input('phone');           // encrypted mutator
        $user->must_change_password = true;
        $user->save();

        $this->audit->allowed('users.create', $user->email, ['role_id' => $user->role_id]);

        return back()->with('status', "{$user->name} can now sign in.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role_id'   => ['sometimes','integer','exists:roles,id'],
            'is_active' => ['sometimes','boolean'],
        ]);

        if (array_key_exists('is_active', $data) && $user->id === $request->user()->id) {
            return back()->withErrors(['is_active' => 'You cannot deactivate the account you are signed in with.']);
        }

        $before = $user->only(['role_id','is_active']);
        $user->update($data);

        $this->audit->allowed('users.update', $user->email, ['from' => $before, 'to' => $data]);

        return back()->with('status', "{$user->name} updated.");
    }
}
