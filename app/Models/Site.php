<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    protected $fillable = ['sector_id','name','commune'];

    public function sector(): BelongsTo { return $this->belongsTo(Sector::class); }
}
