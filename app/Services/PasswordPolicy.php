<?php

namespace App\Services;

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Password lifecycle rules that sit beside the StrongPassword expression:
 * reuse refusal, history retention and the rotation clock.
 */
class PasswordPolicy
{
    public function isReused(User $user, string $candidate): bool
    {
        $keep = (int) config('sldwp.password_history', 5);

        return PasswordHistory::where('user_id', $user->id)
            ->latest('id')->take($keep)->get()
            ->contains(fn ($row) => Hash::check($candidate, $row->password_hash));
    }

    public function change(User $user, string $newPassword): void
    {
        // Remember the outgoing digest, then rotate.
        PasswordHistory::create(['user_id' => $user->id, 'password_hash' => $user->password]);

        $user->forceFill([
            'password'             => Hash::make($newPassword),   // bcrypt, cost from config/hashing
            'password_changed_at'  => now(),
            'must_change_password' => false,
        ])->save();

        $keep = (int) config('sldwp.password_history', 5);
        $stale = PasswordHistory::where('user_id', $user->id)->latest('id')->skip($keep)->take(50)->pluck('id');
        PasswordHistory::whereIn('id', $stale)->delete();
    }
}
