<?php

namespace App\Http\Controllers\hrm;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index()
    {
        $payrollData = Payroll::latest()->get()->map(function ($payroll) {
            return [
                'id' => $payroll->id,
                'employee_id' => $payroll->employee_id,
                'employee_name' => $payroll->employee_name,
                'role' => $payroll->role,
                'base_salary' => $payroll->base_salary,
                'days_worked' => $payroll->days_worked,
                'daily_rate' => $payroll->daily_rate,
                'total_days_amt' => $payroll->total_days_amt,
                'overtime_hours' => $payroll->overtime_hours,
                'ot_rate' => $payroll->ot_rate,
                'ot_amt' => $payroll->ot_amt,
                'night_hours' => $payroll->night_hours,
                'night_rate' => $payroll->night_rate,
                'night_amt' => $payroll->night_amt,
                'sunday_restday_hours' => $payroll->sunday_restday_hours,
                'sun_sp_rate' => $payroll->sun_sp_rate,
                'sun_sp_amt' => $payroll->sun_sp_amt,
                'holiday_amt' => $payroll->holiday_amt,
                'late_minutes' => $payroll->late_minutes,
                'late_rate_min' => $payroll->late_rate_min,
                'late_total_deduction' => $payroll->late_total_deduction,
                'sss_deduction' => $payroll->sss_deduction,
                'philhealth_deduction' => $payroll->philhealth_deduction,
                'pagibig_deduction' => $payroll->pagibig_deduction,
                'sss_loan' => $payroll->sss_loan,
                'pf_loan' => $payroll->pf_loan,
                'gross_pay' => $payroll->gross_pay,
                'net_pay' => $payroll->net_pay,
                'status' => $payroll->status,
                'created_at' => $payroll->created_at,
            ];
        });

        return Inertia::render('Dashboard/HRM/Payroll', [
            'payroll_data' => $payrollData,
            'stats' => [
                'total_payout' => Payroll::where('status', 'approved')->sum('net_pay'),
                'pending_approvals' => Payroll::where('status', 'pending')->count(),
            ],
        ]);
    }
}
