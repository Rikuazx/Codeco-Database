<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
    'teacher_id',
    'student_id',
    'class_session_id',
    'rating',
    'comment',
    'submitted_at',
];
}
