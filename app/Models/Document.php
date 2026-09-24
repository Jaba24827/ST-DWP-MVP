<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title','stored_path','original_name','mime','size_bytes',
        'checksum_sha256','sector_id','classification','owner_id',
    ];

    /** stored_path must never reach a response body. */
    protected $hidden = ['stored_path','checksum_sha256'];

    public function owner(): BelongsTo  { return $this->belongsTo(User::class, 'owner_id'); }
    public function sector(): BelongsTo { return $this->belongsTo(Sector::class); }
    public function downloads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DocumentDownload::class);
    }

    /**
     * Authorisation pushed into the query. A restricted row is never loaded
     * for a role that cannot open it, so there is nothing to leak through a
     * count, a paginator total or a careless view.
     */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->can_('documents.view.restricted')) {
            return $q;
        }
        if ($user->can_('documents.view')) {
            return $q->whereIn('classification', ['public','internal']);
        }

        return $q->whereRaw('1 = 0');
    }

    /** Parameter binding, not string concatenation — the SQL injection boundary. */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        return $term
            ? $q->where('title', 'like', '%'.addcslashes($term, '%_\\').'%')
            : $q;
    }
}
