<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side authorisation gate: perm:documents.upload
 *
 * Hiding a control in the interface is a convenience for the user. This is
 * the control. It runs before the controller, and it writes its own denial
 * so that no refusal can happen silently.
 */
class EnsurePermission
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->can_($permission)) {
            $this->audit->denied($permission, $request->path(), ['method' => $request->method()]);

            abort(403, 'Your role does not hold the permission required for this action.');
        }

        return $next($request);
    }
}
