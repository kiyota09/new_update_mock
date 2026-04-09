<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClockController extends Controller
{
    public function clock()
    {
        $user = Auth::user();
        // Use local timezone for the view context
        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();

        return Inertia::render('Dashboard/USERS/clock', [
            'today_log' => AttendanceLog::where('user_id', $user->id)
                ->where('date', $today)
                ->first(),

            'assigned_shift' => EmployeeShift::where('user_id', $user->id)
                ->where('effective_date', $today)
                ->first(),

            'history' => AttendanceLog::where('user_id', $user->id)
                ->orderBy('date', 'desc')
                ->take(5)
                ->get(),
        ]);
    }

    public function toggle()
    {
        $user = Auth::user();

        /**
         * FORCE TIMEZONE: Explicitly set to Asia/Manila to fix server time offsets.
         * This ensures "02:00 AM" in the Philippines is not recorded as "06:00 PM" (UTC).
         */
        $now = Carbon::now('Asia/Manila');
        $today = $now->toDateString();
        $timeString12hr = $now->format('h:i A');

        $shift = EmployeeShift::where('user_id', $user->id)
            ->where('effective_date', $today)
            ->first();

        $log = AttendanceLog::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // --- CLOCK IN LOGIC ---
        if (! $log) {
            if (! $shift) {
                return redirect()->back()->with('error', 'No shift assigned for today.');
            }

            // Map shift types to exact start times
            $startTimeStr = match ($shift->shift_type) {
                'Morning' => '08:00:00',
                'Afternoon' => '16:00:00', // 4:00 PM
                'Graveyard' => '00:00:00', // 12:00 MN
                default => '08:00:00'
            };

            // Calculate shift start with correct timezone
            $shiftStart = Carbon::createFromFormat('Y-m-d H:i:s', "$today $startTimeStr", 'Asia/Manila');
            $earliestIn = $shiftStart->copy()->subMinutes(30);

            // Rule: Prevent clock-in if more than 30 minutes early
            if ($now->lt($earliestIn)) {
                return redirect()->back()->with('error', 'Too early! Earliest clock-in is at '.$earliestIn->format('h:i A'));
            }

            // Detect On-Time vs Late based on local time comparison
            $status = ($now->gt($shiftStart)) ? 'Late' : 'On-Time';

            AttendanceLog::create([
                'user_id' => $user->id,
                'date' => $today,
                'clock_in' => $timeString12hr,
                'status' => $status,
            ]);

            return redirect()->back()->with('success', "Clocked in as $status.");
        }

        // --- CLOCK OUT LOGIC ---
        elseif ($log && ! $log->clock_out) {
            if (! $shift) {
                $log->update(['clock_out' => $timeString12hr]);

                return redirect()->back();
            }

            $endTimeStr = match ($shift->shift_type) {
                'Morning' => '17:00:00',   // 5:00 PM
                'Afternoon' => '01:00:00',  // 1:00 AM (Next Day)
                'Graveyard' => '09:00:00',  // 9:00 AM
                default => '17:00:00'
            };

            $shiftEnd = Carbon::createFromFormat('Y-m-d H:i:s', "$today $endTimeStr", 'Asia/Manila');

            // Handle shifts ending on the following day
            if (in_array($shift->shift_type, ['Afternoon', 'Graveyard']) && $shiftEnd->hour < 12) {
                $shiftEnd->addDay();
            }

            $finalStatus = $log->status;

            // Rule: Detect Early Out based on local time comparison
            if ($now->lt($shiftEnd)) {
                $finalStatus = $finalStatus.' / Early Out';
            }

            $log->update([
                'clock_out' => $timeString12hr,
                'status' => $finalStatus,
            ]);

            return redirect()->back()->with('success', 'Clocked out successfully.');
        }

        return redirect()->back();
    }
}
