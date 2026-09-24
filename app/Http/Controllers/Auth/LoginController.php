<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * First factor. Deliberately uniform: a wrong address and a wrong password
 * produce the same message and comparable timing, so the form cannot be
 * used to enumerate which addresses exist.
 */
class LoginController extends Controller
{
    public function __construct(
        private AuditLogger $audit,
        private MfaService $mfa,
    ) {}

    public function show()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        $key = $request->throttleKey();

        if (RateLimiter::tooManyAttempts($key, (int) config('sldwp.login_throttle', 5))) {
            $this->audit->denied('auth.login.throttled', $request->input('email'));
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $user = User::with('role.permissions')->where('email', $request->input('email'))->first();

        // Hash::check on a dummy digest when the user is missing keeps the
        // response time comparable in both branches.
        $valid = $user
            ? Hash::check($request->input('password'), $user->password)
            : Hash::check($request->input('password'), '$2y$12$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidin');

        if (! $valid || ! $user) {
            RateLimiter::hit($key, 60);
            $this->audit->denied('auth.login', 'credentials', ['actor' => $request->input('email')]);
            throw ValidationException::withMessages(['email' => 'Those details do not match our records.']);
        }

        if (! $user->is_active || $user->isLocked()) {
            RateLimiter::hit($key, 60);
            $this->audit->denied('auth.login', 'account unavailable', ['actor' => $user->email]);
            throw ValidationException::withMessages(['email' => 'This account is not available. Contact an administrator.']);
        }

        RateLimiter::clear($key);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();                 // session fixation
        $request->session()->put('mfa.verified', false);
        $request->session()->put('mfa.channel', $user->mfa_channel);

        $this->audit->allowed('auth.password.verified', 'credentials');

        if (! $user->mfa_enabled) {
            $request->session()->put('mfa.verified', true);
            return redirect()->intended(route('dashboard'));
        }

        return redirect()->route('mfa.show');
    }

    public function destroy(Request $request)
    {
        $this->audit->allowed('auth.logout', 'session');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
