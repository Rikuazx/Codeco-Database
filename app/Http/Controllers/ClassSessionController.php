<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\ClassSession;
use Carbon\Carbon;
use App\Models\Feedback;
use App\Models\Teacher;
use App\Models\TeacherAvailability;

class ClassSessionController extends Controller
{
public function generateSessions($class_id)
{
    $class = Classes::findOrFail($class_id);


    $existing = ClassSession::where('class_id', $class_id)->exists();

    if ($existing) {
        return response()->json([
         'error' => 'Sessions already generated for this class'
    ], 400);
}

    $startDate = now();
    $total = $class->total_sessions;

    for ($i = 1; $i <= $total; $i++) {
        ClassSession::create([
            'class_id' => $class->id,
            'start_time' => $startDate->copy()->addDays($i * 2)->setTime(10, 0),
            'end_time' => $startDate->copy()->addDays($i * 2)->setTime(12, 0),
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
 //  1. Check teacher availability
    $available = TeacherAvailability::where('teacher_id', $request->teacher_id)
        ->where('date', $session->start_time->toDateString())
        ->where('is_available', true)
        ->where(function ($q) use ($session) {
            $q->where('is_full_day', true)
              ->orWhere(function ($q2) use ($session) {
                  $q2->where('start_time', '<=', $session->start_time->format('H:i:s'))
                     ->where('end_time', '>=', $session->end_time->format('H:i:s'));
              });
        })
        ->exists();

    if (!$available) {
        return response()->json([
            'error' => 'Teacher is not available at this time'
        ], 400);
    }

    //  2. Prevent overlapping sessions
    $conflict = ClassSession::where('teacher_id', $request->teacher_id)
        ->where('id', '!=', $session->id)
        ->where(function ($q) use ($session) {
            $q->whereBetween('start_time', [$session->start_time, $session->end_time])
              ->orWhereBetween('end_time', [$session->start_time, $session->end_time]);
        })
        ->exists();

    if ($conflict) {
        return response()->json([
            'error' => 'Teacher already has another session at this time'
        ], 400);
    }

    //  3. Assign teacher
    $session->update([
        'teacher_id' => $request->teacher_id
    ]);

    return response()->json([
        'message' => 'Teacher assigned successfully',
        'data' => $session
    ]);
}

public function complete($id)
{
    try {
        $session = ClassSession::findOrFail($id);

        $feedbackExists = Feedback::where('class_session_id', $session->id)->exists();

        if (!$feedbackExists) {
            return response()->json([
                'error' => 'Cannot complete session without feedback'
            ], 400);
        }

        $session->status = 'completed';
        $session->save();

        return response()->json([
            'message' => 'Session completed successfully',
            'data' => $session
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

public function autoAssignTeacher($id)
{
    $session = ClassSession::findOrFail($id);

    //  Prevent re-assign
    if ($session->teacher_id) {
        return response()->json([
            'error' => 'Teacher already assigned'
        ], 400);
    }

    //  Find available teacher
    $teacher = Teacher::whereHas('availabilities', function ($q) use ($session) {
        $q->where('date', Carbon::parse($session->start_time)->toDateString())
          ->where('is_available', true)
          ->where(function ($q2) use ($session) {
              $q2->where('is_full_day', true)
                 ->orWhere(function ($q3) use ($session) {
                     $q3->where('start_time', '<=', $session->start_time)
                        ->where('end_time', '>=', $session->end_time);
                 });
          });
    })
    //  Prevent time conflict
    ->whereDoesntHave('classSessions', function ($q) use ($session) {
        $q->whereBetween('start_time', [$session->start_time, $session->end_time]);
    })
    ->first();

    if (!$teacher) {
        return response()->json([
            'error' => 'No available teacher found'
        ], 400);
    }

    // Assign teacher
    $session->update([
        'teacher_id' => $teacher->id
    ]);

    return response()->json([
        'message' => 'Teacher assigned successfully',
        'teacher_id' => $teacher->id
    ]);
}
public function update(Request $request, $id)
{
    $session = ClassSession::findOrFail($id);

    $request->validate([
        'start_time' => 'nullable|date',
        'end_time' => 'nullable|date|after:start_time',
        'teacher_id' => 'nullable|exists:teachers,id',
        'status' => 'nullable|in:scheduled,ongoing,completed'
    ]);

    $session->update($request->only([
        'start_time',
        'end_time',
        'teacher_id',
        'status'
    ]));

    return response()->json([
        'message' => 'Session updated',
        'data' => $session
    ]);
}
public function index()
{
    return ClassSession::with(['teacher', 'class'])->get();
}
}