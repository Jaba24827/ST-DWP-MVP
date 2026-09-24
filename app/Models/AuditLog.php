<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id','actor','action','target','result','ip','user_agent','context','created_at'];
    protected $casts = ['created_at' => 'datetime', 'context' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeOfKind(Builder $q, ?string $kind): Builder
    {
        return match ($kind) {
            'auth'   => $q->where('action', 'like', 'auth.%'),
            'denied' => $q->where('result', 'denied'),
            'change' => $q->where('result', 'allowed')->where('action', 'not like', 'auth.%'),
            default  => $q,
        };
    }
}
