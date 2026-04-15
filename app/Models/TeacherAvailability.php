<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAvailability extends Model
{
protected $fillable = [
    'teacher_id',
    'period_start',
    'period_end',
    'type',
    'start_time',
    'end_time',
    'submitted_at',
];

}
