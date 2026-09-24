<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name','description','is_system'];
    protected $casts = ['is_system' => 'boolean'];

    public function permissions(): BelongsToMany { return $this->belongsToMany(Permission::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }

    public function hasPermission(string $key): bool
    {
        // relationLoaded check keeps this O(1) per request instead of O(n) queries.
        if (! $this->relationLoaded('permissions')) {
            $this->load('permissions');
        }

        return $this->permissions->contains('key', $key);
    }
}
