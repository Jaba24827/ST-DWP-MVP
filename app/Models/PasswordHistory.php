<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordHistory extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id','password_hash','created_at'];
    protected $hidden = ['password_hash'];
    protected $attributes = [];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->created_at = $m->created_at ?? now());
    }
}
