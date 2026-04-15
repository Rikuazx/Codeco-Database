<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
public function sessions()
{
    return $this->hasMany(ClassSession::class);
}
}
