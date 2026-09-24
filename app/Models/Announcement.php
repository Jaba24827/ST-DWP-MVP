<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = ['title','body','audience','author_id','published_at','expires_at'];
    protected $casts = ['published_at' => 'datetime', 'expires_at' => 'datetime'];

    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }

    public function scopeForUser(Builder $q, User $user): Builder
    {
        return $q->whereNotNull('published_at')
            ->where(fn ($s) => $s->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($s) => $s->where('audience', 'all')
                                 ->orWhere('audience', $user->sector?->name));
    }
}
