<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
protected $fillable = [
    'class_id',
    'start_time',
    'end_time',
    'teacher_id'
];

}
