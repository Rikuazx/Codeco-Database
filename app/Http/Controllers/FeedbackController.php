<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;


class FeedbackController extends Controller
{
   public function store(Request $request)
{
    $request->validate([
        'teacher_id' => 'required|exists:teachers,id',
        'student_id' => 'required|exists:students,id',
        'class_session_id' => 'required|exists:class_sessions,id',
        'rating' => 'nullable|integer|min:1|max:5',
        'comment' => 'nullable|string',
    ]);

    // ❗ prevent empty feedback
    if (!$request->rating && !$request->comment) {
        return response()->json([
            'error' => 'Feedback cannot be empty'
        ], 400);
    }

    // ❗ prevent duplicate feedback per session
    $exists = Feedback::where('class_session_id', $request->class_session_id)
        ->where('teacher_id', $request->teacher_id)
        ->exists();

    if ($exists) {
        return response()->json([
            'error' => 'Feedback already submitted for this session'
        ], 400);
    }

    $feedback = Feedback::create([
        'teacher_id' => $request->teacher_id,
        'student_id' => $request->student_id,
        'class_session_id' => $request->class_session_id,
        'rating' => $request->rating,
        'comment' => $request->comment,
        'submitted_at' => now(),
    ]);

    return response()->json([
        'message' => 'Feedback submitted',
        'data' => $feedback
    ]);
}
}
