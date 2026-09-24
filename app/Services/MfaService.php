<?php

namespace App\Services;

use App\Models\MfaCode;
use App\Models\User;
use App\Notifications\MfaCodeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Second authentication factor.
 *
 * A six-digit code is generated with a cryptographically secure source,
 * hashed before storage, delivered by email or SMS, and verified in
 * constant time. The plaintext code exists only inside this method and
 * inside the notification — it is never written to the database or the log.
 */
class MfaService
{
    public function __construct(private AuditLogger $audit) {}

    public function issue(User $user, string $channel): MfaCode
    {
        // Invalidate anything still outstanding: one live challenge per user.
        MfaCode::where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $record = MfaCode::create([
            'user_id'     => $user->id,
            'code_hash'   => Hash::make($code),
            'channel'     => $channel,
            'destination' => $channel === 'sms' ? $user->phone : $user->email,
            'expires_at'  => now()->addSeconds((int) config('sldwp.mfa_ttl', 300)),
            'ip'          => request()->ip(),
        ]);

        $user->notify(new MfaCodeNotification($code, $channel));

        $this->audit->allowed('auth.mfa.challenged', $user->maskedDestination($channel), ['channel' => $channel]);

        return $record;
    }

    /** @return array{ok: bool, reason: ?string} */
    public function verify(User $user, string $entered): array
    {
        $record = MfaCode::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $record) {
            $this->audit->denied('auth.mfa.missing', 'no live challenge');
            return ['ok' => false, 'reason' => 'Request a new code.'];
        }

        if ($record->expires_at->isPast()) {
            $this->audit->denied('auth.mfa.expired', 'challenge #'.$record->id);
            return ['ok' => false, 'reason' => 'That code has expired. Send a new one.'];
        }

        $max = (int) config('sldwp.mfa_max_attempts', 5);

        if ($record->attempts >= $max) {
            $record->update(['consumed_at' => now()]);
            $this->audit->denied('auth.mfa.locked', 'attempt limit reached');
            return ['ok' => false, 'reason' => 'Too many incorrect codes. Start again.'];
        }

        $record->increment('attempts');

        // Hash::check is constant-time; a string comparison here would leak timing.
        if (! Hash::check($entered, $record->code_hash)) {
            $this->audit->denied('auth.mfa.failed', 'attempt '.$record->attempts);
            $left = $max - $record->attempts;
            return ['ok' => false, 'reason' => "That code is not correct. {$left} attempts left."];
        }

        $record->update(['consumed_at' => now()]);
        $this->audit->allowed('auth.mfa.verified', 'session');

        return ['ok' => true, 'reason' => null];
    }

    public function newSessionToken(): string
    {
        return Str::random(40);
    }
}
