<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Centralised audit service (application tier).
 *
 * Controllers and middleware call this instead of writing rows themselves,
 * so every record has the same shape and nothing can quietly skip the log.
 * The application database account holds no UPDATE or DELETE grant on
 * audit_logs, which makes the table append-only in practice.
 */
class AuditLogger
{
    public function record(string $action, ?string $target, string $result, array $context = [], ?Request $request = null): void
    {
        $user    = Auth::user();
        $request = $request ?: request();

        AuditLog::create([
            'user_id'    => $user?->id,
            'actor'      => $user?->email ?? ($context['actor'] ?? 'unauthenticated'),
            'action'     => $action,
            'target'     => $target ? mb_substr($target, 0, 190) : null,
            'result'     => $result,
            'ip'         => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 255),
            'context'    => $context ?: null,
            'created_at' => now(),
        ]);
    }

    public function allowed(string $action, ?string $target = null, array $ctx = []): void
    {
        $this->record($action, $target, 'allowed', $ctx);
    }

    public function denied(string $action, ?string $target = null, array $ctx = []): void
    {
        $this->record($action, $target, 'denied', $ctx);
    }
}
