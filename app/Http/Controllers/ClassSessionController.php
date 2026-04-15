<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\ClassSession;
use Carbon\Carbon;


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
            'message' => 'Sessions generated'
        ]);
    }

    public function assignTeacher(Request $request)
{
    $request->validate([
        'session_id' => 'required|exists:class_sessions,id',
        'teacher_id' => 'required|exists:teachers,id',
    ]);

    $session = \App\Models\ClassSession::findOrFail($request->session_id);

    $session->teacher_id = $request->teacher_id;
    $session->save();

    return response()->json([
        'message' => 'Teacher assigned successfully',
        'data' => $session
    ]);
}
}