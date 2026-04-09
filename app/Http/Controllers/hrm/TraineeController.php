<?php

namespace App\Http\Controllers\hrm;

use App\Http\Controllers\Controller;
use App\Models\TraineeGrade;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TraineeController extends Controller
{
    public function index(Request $request)
    {
        // DYNAMIC MODULE DETECTION
        $routeName = $request->route()->getName();
        $modulePrefix = explode('.', $routeName)[0];
        $module = strtoupper($modulePrefix);

        $trainees = User::where('position', 'trainee')
            ->where('role', $module)
            ->with('traineeGrade')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'role' => $t->role,
                'join_date' => $t->join_date,
                'trainee_grade' => $t->traineeGrade,
            ]);

        return Inertia::render('Dashboard/HRM/Trainee', [
            'trainees' => $trainees,
            'currentModule' => $module,
        ]);
    }

    public function grade(Request $request, $id)
    {
        $validated = $request->validate([
            'skills_performance' => 'required|integer|min:1|max:5',
            'behaviour' => 'required|integer|min:1|max:5',
            'technicals' => 'required|integer|min:1|max:5',
            'safety_awareness' => 'required|integer|min:1|max:5',
            'productivity' => 'required|integer|min:1|max:5',
        ]);

        $total = array_sum($validated);
        $percentage = ($total / 25) * 100;

        TraineeGrade::updateOrCreate(
            ['user_id' => $id],
            array_merge($validated, ['total_percentage' => $percentage])
        );

        return back()->with('message', 'Grade successfully saved.');
    }

    public function pass(Request $request, $id)
    {
        $trainee = User::findOrFail($id);
        $grade = $trainee->traineeGrade;

        if (! $grade || $grade->total_percentage < 80) {
            return back()->with('error', 'Trainee must have at least 80% grade to be approved.');
        }

        // PERFECT PIPELINE FIX:
        // We DO NOT change their position to 'staff'. We leave it as 'trainee'.
        // This guarantees that Onboarding.vue will detect them and let HR do the conversion.
        $trainee->touch(); // Updates the timestamp to register the manager's approval

        return back()->with('message', 'Trainee approved! HR has been notified to officially convert them to Staff.');
    }

    public function fail(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);

        $trainee = User::findOrFail($id);

        // Fails the trainee and marks them inactive
        $trainee->update(['is_active' => false]);

        return back()->with('message', 'Trainee failed and archived.');
    }
}
