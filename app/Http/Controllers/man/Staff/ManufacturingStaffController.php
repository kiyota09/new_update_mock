<?php

namespace App\Http\Controllers\man\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

abstract class ManufacturingStaffController extends Controller
{
    /**
     * Get the currently authenticated staff member.
     *
     * @return \App\Models\User
     */
    protected function staff()
    {
        return Auth::user();
    }

    /**
     * Get the staff's current shift.
     * If a shift field exists on the user, it uses that; otherwise,
     * it tries to fetch the shift from the employee_shifts table for today.
     *
     * @return string
     */
    protected function getShift()
    {
        $user = $this->staff();

        // Option 1: if the user has a direct 'shift' attribute
        if (isset($user->shift)) {
            return $user->shift;
        }

        // Option 2: fetch from employee_shifts table
        $today = now()->toDateString();
        $shift = \App\Models\EmployeeShift::where('user_id', $user->id)
            ->where('effective_date', $today)
            ->first();

        return $shift ? $shift->shift_type : 'Morning'; // fallback
    }

    /**
     * Generate a unique code for a record.
     * Format: PREFIX-YYYY-MM-XXXXX (e.g., FABRIC-2026-03-00001)
     *
     * @param  string  $prefix
     * @param  string  $modelClass  Fully qualified model class name
     * @param  string  $codeColumn  Column name where the code is stored
     * @return string
     */
    protected function generateCode($prefix, $modelClass, $codeColumn = 'code')
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $prefix = strtoupper($prefix);

        $lastCode = $modelClass::where($codeColumn, 'LIKE', "{$prefix}-{$year}-{$month}-%")
            ->orderBy($codeColumn, 'desc')
            ->first();

        if ($lastCode) {
            $parts = explode('-', $lastCode->{$codeColumn});
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%s-%05d', $prefix, $year, $month, $newNumber);
    }
}
