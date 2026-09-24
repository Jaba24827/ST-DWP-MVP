<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A session that has passed the password but not the code is authenticated
 * only as far as the MFA screen. Everything else redirects back.
 */
class EnsureMfaVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mfa_enabled && ! $request->session()->get('mfa.verified')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Multi-factor verification required.'], 423)
                : redirect()->route('mfa.show');
        }

        if ($user && $user->must_change_password && ! $request->routeIs('account.password.*')) {
            return redirect()->route('account.password.edit')
                ->with('status', 'Choose a new password before continuing.');
        }

        return $next($request);
    }
}
