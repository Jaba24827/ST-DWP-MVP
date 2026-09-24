<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name','email','password','role_id','sector_id','site_id','position',
        'phone_encrypted','phone_last2','mfa_channel','mfa_enabled','is_active','must_change_password',
    ];

    protected $hidden = ['password','remember_token','phone_encrypted'];

    protected function casts(): array
    {
        return [
            'password'             => 'hashed',
            'is_active'            => 'boolean',
            'mfa_enabled'          => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at'  => 'datetime',
            'locked_until'         => 'datetime',
        ];
    }

    public function role(): BelongsTo   { return $this->belongsTo(Role::class); }
    public function sector(): BelongsTo { return $this->belongsTo(Sector::class); }
    public function site(): BelongsTo   { return $this->belongsTo(Site::class); }
    public function passwordHistories(): HasMany { return $this->hasMany(PasswordHistory::class); }

    /** Phone is encrypted at rest; only this accessor decrypts it. */
    public function getPhoneAttribute(): ?string
    {
        return $this->phone_encrypted ? Crypt::decryptString($this->phone_encrypted) : null;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone_encrypted'] = $value ? Crypt::encryptString($value) : null;
        $this->attributes['phone_last2']     = $value ? substr(preg_replace('/\D/', '', $value), -2) : null;
    }

    /** What the interface is allowed to show about a delivery destination. */
    public function maskedDestination(string $channel): string
    {
        if ($channel === 'sms') {
            return '•••• '.($this->phone_last2 ?? '••');
        }
        [$local, $domain] = explode('@', $this->email);

        return substr($local, 0, 2).str_repeat('•', max(strlen($local) - 2, 2)).'@'.$domain;
    }

    /**
     * The one authorisation question in the system. Everything — middleware,
     * controllers, query scopes, Blade, the React navigation — routes here.
     */
    public function can_(string $permission): bool
    {
        return $this->is_active
            && $this->role
            && $this->role->hasPermission($permission);
    }

    public function permissionKeys(): array
    {
        return $this->role ? $this->role->permissions->pluck('key')->all() : [];
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
