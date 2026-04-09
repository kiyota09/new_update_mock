<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'position',
        'profile_photo_path',
        'employee_id',
        'department',
        'join_date',
        'is_active',
        // Fields for promotion suggestion logic
        'promotion_suggested',
        'suggested_at',
        'manufacturing_role',
        'is_manufacturing_supervisor',
        'supervisor_department',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Casting new fields for proper data handling
            'promotion_suggested' => 'boolean',
            'suggested_at' => 'datetime',
            'is_manufacturing_supervisor' => 'boolean',
        ];
    }

    /**
     * Scope for HRM department users
     */
    public function scopeHrm($query)
    {
        return $query->where('role', 'HRM');
    }

    /**
     * Scope for SCM department users
     */
    public function scopeScm($query)
    {
        return $query->where('role', 'SCM');
    }

    public function scopeFin($query)
    {
        return $query->where('role', 'FIN');
    }

    public function scopeMan($query)
    {
        return $query->where('role', 'MAN');
    }

    public function scopeInv($query)
    {
        return $query->where('role', 'INV');
    }

    public function scopeOrd($query)
    {
        return $query->where('role', 'ORD');
    }

    public function scopeWar($query)
    {
        return $query->where('role', 'WAR');
    }

    public function scopeCrm($query)
    {
        return $query->where('role', 'CRM');
    }

    public function scopeEco($query)
    {
        return $query->where('role', 'ECO');
    }

    public function traineeGrade()
    {
        return $this->hasOne(TraineeGrade::class);
    }

    public function leaveRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get all attendance logs for the user.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Get the most recent attendance log (used in the Controller).
     */
    public function latestAttendance(): HasOne
    {
        return $this->hasOne(AttendanceLog::class)->latestOfMany('date');
    }

    /**
     * Get the shifts assigned to the user.
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    /**
     * Get the current active shift (used in the Controller).
     */
    public function currentShift(): HasOne
    {
        // This helps the 'with' query in your controller find today's shift
        return $this->hasOne(EmployeeShift::class)->latestOfMany('effective_date');
    }

    public function leads()
    {
        return $this->hasMany(CrmLead::class, 'assigned_staff_id');
    }

    public function interactions()
    {
        return $this->hasMany(CrmInteraction::class);
    }

    public function performedLogs()
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }

    public function receivedLogs()
    {
        return $this->hasMany(AuditLog::class, 'target_id');
    }

    public function auditLogs()
    {
        // The foreign key is 'target_id' based on your SQL file
        return $this->hasMany(AuditLog::class, 'target_id');
    }

    // Relationships for manufacturing
    public function fabrics()
    {
        return $this->hasMany(Fabric::class, 'operator_id');
    }

    public function dyeJobs()
    {
        return $this->hasMany(DyeJob::class, 'operator_id');
    }

    public function softenerJobs()
    {
        return $this->hasMany(SoftenerJob::class, 'operator_id');
    }

    public function squeezerJobs()
    {
        return $this->hasMany(SqueezerJob::class, 'operator_id');
    }

    public function ironJobs()
    {
        return $this->hasMany(IronJob::class, 'operator_id');
    }

    public function formJobs()
    {
        return $this->hasMany(FormJob::class, 'operator_id');
    }

    public function packages()
    {
        return $this->hasMany(Package::class, 'operator_id');
    }

    public function reportedMachineReports()
    {
        return $this->hasMany(MachineReport::class, 'reported_by');
    }

    public function resolvedMachineReports()
    {
        return $this->hasMany(MachineReport::class, 'resolved_by');
    }

    public function hasPagePermission($module, $page)
    {
        // Superusers (CEO, secretary, general managers) have full access
        if (in_array($this->position, ['ceo', 'secretary', 'general_manager'])) {
            return true;
        }
        // Module managers can access all pages of their module
        if ($this->position === 'manager' && $this->role === $module) {
            return true;
        }

        // For staff, check page_permissions table
        return PagePermission::where('user_id', $this->id)
            ->where('module', $module)
            ->where('page', $page)
            ->exists();
    }

    /**
     * Get the page permissions associated with the user.
     */
    public function pagePermissions()
    {
        return $this->hasMany(PagePermission::class);
    }

    public function workforcePermissions()
    {
        return $this->hasMany(WorkforcePermission::class);
    }

    /**
     * Check if user can access workforce module for a specific module/department.
     *
     * @param  string|null  $module  e.g., 'HRM', null means any module
     * @param  string|null  $department  e.g., 'dyeing'
     * @param  string  $requiredLevel  'view', 'schedule', 'manage'
     * @return bool
     */
    public function canAccessWorkforce($module = null, $department = null, $requiredLevel = 'view')
    {
        // 1. CEO always has full access
        if ($this->role === 'CEO') {
            return true;
        }

        // 2. Secretary or General Manager: check if they have 'WRF' in granted_modules
        if (in_array($this->position, ['secretary', 'general_manager'])) {
            $grantedModules = $this->moduleAccess->pluck('module')->toArray();
            if (in_array('WRF', $grantedModules)) {
                // They have full access (manage level) to workforce module
                return true;
            }
            // If they don't have WRF granted, they cannot access workforce pages
            return false;
        }

        // 3. For all other users (managers, staff, supervisors): check workforce_permissions table
        $permissions = $this->workforcePermissions;
        if ($permissions->isEmpty()) {
            return false;
        }

        // Define access level hierarchy
        $levelRank = [
            'view'     => 1,
            'schedule' => 2,
            'manage'   => 3,
        ];
        $requiredRank = $levelRank[$requiredLevel] ?? 1;

        foreach ($permissions as $perm) {
            // Check module match: if $module is provided, permission must have same module OR null (wildcard)
            $moduleMatch = is_null($module) || is_null($perm->module) || $perm->module === $module;
            // Check department match: if $department is provided, permission must have same department OR null (wildcard)
            $deptMatch   = is_null($department) || is_null($perm->department) || $perm->department === $department;

            if ($moduleMatch && $deptMatch) {
                $permRank = $levelRank[$perm->access_level] ?? 0;
                if ($permRank >= $requiredRank) {
                    return true;
                }
            }
        }

        return false;
    }

    public function crmPagePermissions()
    {
        return $this->hasMany(CrmPagePermission::class);
    }

    public function canAccessCrmPage($page)
    {
        // CEO always has full access
        if ($this->role === 'CEO') {
            return true;
        }

        // Managers can access all pages? Not necessarily; we'll check permissions
        // But for simplicity, we'll rely on the permissions table.
        return CrmPagePermission::where('user_id', $this->id)->where('page', $page)->exists();
    }

    // Warehouse and Inventory Access Relationships
    public function warehouseAccess()
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse_access', 'user_id', 'warehouse_id');
    }

    public function inventoryAccess()
    {
        return $this->belongsToMany(Warehouse::class, 'user_inventory_access', 'user_id', 'warehouse_id');
    }

    // app/Models/User.php
    public function proAccess()
    {
        return $this->hasOne(ProAccess::class);
    }

    /**
     * Check if user has any warehouse assigned (raw DB query to avoid ambiguous column).
     */
    public function hasWarehouseAccess(): bool
    {
        return \DB::table('user_warehouse_access')
            ->where('user_id', $this->id)
            ->exists();
    }

    /**
     * Check if user has any inventory access (raw DB query).
     */
    public function hasInventoryAccess(): bool
    {
        return \DB::table('user_inventory_access')
            ->where('user_id', $this->id)
            ->exists();
    }

    public function assignedWarehouses()
    {
        return $this->warehouseAccess();
    }

    public function scmAccess()
    {
        return $this->hasOne(ScmAccessPermission::class);
    }

    public function supervisorRoles()
    {
        return $this->hasMany(ManufacturingSupervisorRole::class);
    }

    public function isManufacturingSupervisor()
    {
        return $this->is_manufacturing_supervisor;
    }

    public function getAssignedManufacturingRoles()
    {
        return $this->supervisorRoles->pluck('manufacturing_role')->toArray();
    }

    /**
     * Get the module access entries for this user (secretary/general manager).
     */
    public function moduleAccess()
    {
        return $this->hasMany(UserModuleAccess::class);
    }

    public function manufacturingSupervisorRoles()
{
    return $this->hasMany(ManufacturingSupervisorRole::class);
}

/**
 * Get the list of manufacturing roles this supervisor can oversee (based on department).
 */
public function getSupervisedRolesAttribute()
{
    if (!$this->is_manufacturing_supervisor || !$this->supervisor_department) {
        return [];
    }

    return match ($this->supervisor_department) {
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
}

/**
 * Check if this supervisor can manage a given manufacturing role.
 */
public function canSuperviseRole(string $role): bool
{
    return in_array($role, $this->supervised_roles);
}

/**
 * Get all staff members under this supervisor's department.
 */
public function getDepartmentStaff()
{
    if (!$this->is_manufacturing_supervisor) {
        return collect();
    }

    $roles = $this->supervised_roles;
    return User::where('role', 'MAN')
        ->where('position', 'staff')
        ->whereIn('manufacturing_role', $roles)
        ->get();
}

    /**
     * Helper method to check if user can access a specific module.
     * For secretaries/GMs: if granted_modules is empty → fallback to original role.
     * Once any module is explicitly granted, only those modules are allowed.
     */
    public function canAccessModule(string $module): bool
    {
        // CEO always has access to everything
        if ($this->role === 'CEO') {
            return true;
        }

        // For secretaries and general managers
        if (in_array($this->position, ['secretary', 'general_manager'])) {
            $granted = $this->moduleAccess->pluck('module')->toArray();

            // If no modules have been explicitly granted yet,
            // fall back to the user's original role (e.g., HRM, MAN, etc.)
            if (empty($granted)) {
                return $this->role === $module;
            }

            // Otherwise, check only the explicitly granted modules
            return in_array($module, $granted);
        }

        // Normal managers/staff have default access based on their role
        return $this->role === $module;
    }

    /**
     * Get list of modules this user is explicitly granted (for frontend).
     */
    public function getGrantedModulesAttribute()
    {
        return $this->moduleAccess->pluck('module')->toArray();
    }

    /**
     * Get appropriate dashboard path based on department and position.
     * For secretaries and general managers, redirect to the manager dashboard
     * of their original role (since they retain access via fallback).
     */
    public function getDashboardPathAttribute(): string
    {
        // If the position is trainee, redirect to a single unified trainee dashboard
        if ($this->position === 'trainee') {
            return route('trainee.dashboard');
        }

        // Secretaries and general managers: use the manager dashboard of their role
        if (in_array($this->position, ['secretary', 'general_manager'])) {
            return match ($this->role) {
                'HRM' => route('hrm.manager.dashboard'),
                'SCM' => route('scm.manager.dashboard'),
                'FIN' => route('fin.manager.dashboard'),
                'MAN' => route('man.manager.dashboard'),
                'INV' => route('inv.manager.dashboard'),
                'ORD' => route('ord.manager.dashboard'),
                'WAR' => route('war.manager.dashboard'),
                'CRM' => route('crm.manager.dashboard'),
                'ECO' => route('eco.manager.dashboard'),
                'PRO' => route('pro.manager.dashboard'),
                'PROJ' => route('proj.manager.dashboard'),
                'IT' => route('it.manager.dashboard'),
                default => route('dashboard'),
            };
        }

        // For normal managers and staff
        return match ([$this->role, $this->position]) {
            ['HRM', 'manager'] => route('hrm.manager.dashboard'),
            ['HRM', 'staff'] => route('hrm.employee.dashboard'),
            ['SCM', 'manager'] => route('scm.manager.dashboard'),
            ['SCM', 'staff'] => route('scm.employee.dashboard'),
            ['FIN', 'manager'] => route('fin.manager.dashboard'),
            ['FIN', 'staff'] => route('fin.employee.dashboard'),
            ['MAN', 'manager'] => route('man.manager.dashboard'),
            ['MAN', 'staff'] => route('man.employee.dashboard'),
            ['INV', 'manager'] => route('inv.manager.dashboard'),
            ['INV', 'staff'] => route('inv.employee.dashboard'),
            ['ORD', 'manager'] => route('ord.manager.dashboard'),
            ['ORD', 'staff'] => route('ord.employee.dashboard'),
            ['WAR', 'manager'] => route('war.manager.dashboard'),
            ['WAR', 'staff'] => route('war.employee.dashboard'),
            ['CRM', 'manager'] => route('crm.manager.dashboard'),
            ['CRM', 'staff'] => route('crm.employee.dashboard'),
            ['ECO', 'manager'] => route('eco.manager.dashboard'),
            ['ECO', 'staff'] => route('eco.employee.dashboard'),
            ['PRO', 'manager'] => route('pro.manager.dashboard'),
            ['PRO', 'staff'] => route('pro.employee.dashboard'),
            ['PROJ', 'manager'] => route('proj.manager.dashboard'),
            ['PROJ', 'staff'] => route('proj.employee.dashboard'),
            ['IT', 'manager'] => route('it.manager.dashboard'),
            ['IT', 'staff'] => route('it.employee.dashboard'),
            default => route('dashboard'),
        };
    }
}