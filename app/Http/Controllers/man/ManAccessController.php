<?php

namespace App\Http\Controllers\man;

use App\Http\Controllers\Controller;
use App\Models\ManufacturingSupervisorRole;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManAccessController extends Controller
{
    public function index()
    {
        $staff = User::where('role', 'MAN')
            ->where('position', 'staff')
            ->with('supervisorRoles')
            ->get(['id', 'name', 'email', 'manufacturing_role', 'is_manufacturing_supervisor', 'supervisor_department'])
            ->map(function ($user) {
                // Determine which department this user would supervise if promoted
                $possibleDepartment = $this->getDepartmentFromRole($user->manufacturing_role);
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'manufacturing_role' => $user->manufacturing_role,
                    'is_manufacturing_supervisor' => $user->is_manufacturing_supervisor,
                    'supervisor_department' => $user->supervisor_department,
                    'supervisor_roles' => $user->supervisorRoles,
                    'possible_department' => $possibleDepartment,
                ];
            });

        return Inertia::render('Dashboard/MAN/Manager/Access', [
            'staff' => $staff,
        ]);
    }

    public function assignSupervisor(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_supervisor' => 'required|boolean',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($request->is_supervisor) {
            // Derive department from the user's manufacturing role
            $department = $this->getDepartmentFromRole($user->manufacturing_role);
            
            if (!$department) {
                return back()->with('error', 'User does not have a valid manufacturing role to become a supervisor.');
            }

            $user->is_manufacturing_supervisor = true;
            $user->supervisor_department = $department;

            // Auto-assign all roles belonging to this department
            $rolesToAssign = match ($department) {
                'knitting' => ['knitting_yarn'],
                'dyeing' => [
                    'dyeing_color',
                    'dyeing_fabric_softener',
                    'dyeing_squeezer',
                    'dyeing_ironing',
                    'dyeing_forming',
                    'dyeing_packaging',
                    'checker_quality',
                ],
                'maintenance' => ['maintenance_checker'],
                default => [],
            };

            ManufacturingSupervisorRole::where('user_id', $user->id)->delete();
            foreach ($rolesToAssign as $role) {
                ManufacturingSupervisorRole::create([
                    'user_id' => $user->id,
                    'manufacturing_role' => $role,
                ]);
            }
        } else {
            // Demote
            $user->is_manufacturing_supervisor = false;
            $user->supervisor_department = null;
            ManufacturingSupervisorRole::where('user_id', $user->id)->delete();
        }

        $user->save();

        return back()->with('message', $request->is_supervisor ? 'Staff promoted to supervisor.' : 'Supervisor demoted.');
    }

    /**
     * Get the department that a given manufacturing role belongs to.
     */
    private function getDepartmentFromRole(?string $role): ?string
    {
        return match ($role) {
            'knitting_yarn' => 'knitting',
            'dyeing_color', 'dyeing_fabric_softener', 'dyeing_squeezer', 
            'dyeing_ironing', 'dyeing_forming', 'dyeing_packaging', 'checker_quality' => 'dyeing',
            'maintenance_checker' => 'maintenance',
            default => null,
        };
    }
}