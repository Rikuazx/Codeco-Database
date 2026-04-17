<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\ClassSession;
use Carbon\Carbon;
use App\Models\Feedback;

class ClassSessionController extends Controller
{
    public function generateSessions($class_id)
    {
        $class = Classes::findOrFail($class_id);

        $startDate = Carbon::now();
        $total = $class->total_session;

        for ($i = 1; $i <= $total; $i++) {
            ClassSession::create([
                'class_id' => $class->id,
                'session_number' => $i,
                'session_date' => $startDate->copy()->addDays($i * 2),
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'status' => 'scheduled',
            ]);
        }

          return response()->json([
        'message' => 'Session generated',
        'class_id' => $class_id
    ]);
    }

    public function assignTeacher(Request $request)
{
    $request->validate([
        'session_id' => 'required|exists:class_sessions,id',
        'teacher_id' => 'required|exists:teachers,id',
    ]);

    $session = ClassSession::findOrFail($request->session_id);

    $session->teacher_id = $request->teacher_id;
    $session->save();

    return response()->json([
        'message' => 'Teacher assigned successfully',
        'data' => $session
    ]);
}
public function complete($id)
{
    $session = ClassSession::findOrFail($id);

    // ❗ Check if already completed
    if ($session->status === 'completed') {
        return response()->json([
            'error' => 'Session already completed'
        ], 400);
    }

    // ❗ Check feedback exists
    $feedbackExists = Feedback::where('class_session_id', $session->id)->exists();

    if (!$feedbackExists) {
        return response()->json([
            'error' => 'Cannot complete session without feedback'
        ], 400);
    }

    // ✅ Mark completed
    $session->status = 'completed';
    $session->save();

    return response()->json([
        'message' => 'Session completed successfully',
        'data' => $session
    ]);
}
}