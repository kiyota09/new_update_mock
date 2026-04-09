<?php

namespace App\Http\Controllers\hrm;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HrmDashboardController extends Controller
{
    /**
     * Display the unified HRM Dashboard.
     * Accessible by CEO, Secretary, General Managers, and HRM-specific roles.
     */
    public function index()
    {
        $user = Auth::user();

        // 1. Hierarchy & Role-Based Access Check
        $hasAccess = in_array(strtoupper($user->role), ['HRM', 'CEO']) ||
                     in_array(strtolower($user->position), ['secretary', 'general_manager', 'general manager']);

        if (! $hasAccess) {
            abort(403, 'Unauthorized access to HRM Module.');
        }

        // 2. Statistics for the HRMDashboard.vue component
        $stats = [
            'total_employees' => User::whereIn('position', ['staff', 'manager', 'general_manager', 'secretary'])->count(),
            'active_trainees' => User::where('position', 'trainee')->count(),
            'pending_applications' => Applicant::where('status', 'pending')
                ->where('archived', false)
                ->count(),
            'pending_interviews' => Applicant::where('status', 'Interview')
                ->where('archived', false)
                ->count(),
            'pending_onboarding' => User::where('position', 'trainee')
                ->where('is_active', true)
                ->count(),
            'rejected_count' => Applicant::where('archived', true)->count(),
        ];

        // 3. Department distribution (for chart)
        $departments = ['HRM', 'MAN','CRM', 'LOG'];
        $departmentCounts = [];
        foreach ($departments as $dept) {
            $departmentCounts[$dept] = User::where('role', $dept)
                ->where('position', '!=', 'trainee')
                ->where('is_active', true)
                ->count();
        }

        // 4. Attendance trend (last 6 months placeholder)
        $months = [];
        $attendanceValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = Carbon::now()->subMonths($i)->format('M');
            $attendanceValues[] = rand(92, 98); // Placeholder – replace with real data if available
        }

        $attendanceTrend = [
            'months' => $months,
            'values' => $attendanceValues,
        ];

        // 5. Render the component
        return Inertia::render('Dashboard/HRM/HRMDashboard', [
            'stats' => $stats,
            'departmentCounts' => $departmentCounts,
            'attendanceTrend' => $attendanceTrend,
        ]);
    }
}
