<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentDownload extends Model
{
    public $timestamps = false;
    protected $fillable = ['document_id','user_id','ip','created_at'];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->created_at = $m->created_at ?? now());
    }

    public function document(): BelongsTo { return $this->belongsTo(Document::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
}
