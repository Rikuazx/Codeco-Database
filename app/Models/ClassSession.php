<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
public function class()
{
    return $this->belongsTo(Classes::class);
}

public function teacher()
{
    return $this->belongsTo(Teacher::class);
}
}
