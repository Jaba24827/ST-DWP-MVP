<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Rules\StrongPassword;
use App\Services\AuditLogger;
use App\Services\PasswordPolicy;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function __construct(
        private PasswordPolicy $policy,
        private AuditLogger $audit,
    ) {}

    public function edit(Request $request)
    {
        return view('account.security', [
            'user'    => $request->user(),
            'pattern' => StrongPassword::jsPattern(),
            'recent'  => $request->user()->id
                ? \App\Models\AuditLog::where('user_id', $request->user()->id)
                    ->where('action', 'like', 'auth.%')->latest('id')->take(6)->get()
                : collect(),
        ]);
    }

    public function update(UpdatePasswordRequest $request)
    {
        $user = $request->user();

        if ($this->policy->isReused($user, $request->input('password'))) {
            $this->audit->denied('account.password.change', 'reuse refused');

            return back()->withErrors(['password' => 'Choose a password you have not used before.']);
        }

        $this->policy->change($user, $request->input('password'));

        $this->audit->allowed('account.password.change', 'own account');

        return back()->with('status', 'Password changed. The new one is required at your next sign-in.');
    }
}
