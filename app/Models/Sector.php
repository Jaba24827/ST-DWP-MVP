<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    protected $fillable = ['name','description'];

    public function sites(): HasMany { return $this->hasMany(Site::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
}
