<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfaCode extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id','code_hash','channel','destination','attempts','consumed_at','expires_at','ip','created_at'];
    protected $hidden = ['code_hash'];
    protected $casts = ['expires_at' => 'datetime', 'consumed_at' => 'datetime', 'created_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
