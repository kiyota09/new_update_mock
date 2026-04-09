<?php

namespace App\Http\Controllers\ceo;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserModuleAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CeoAccessController extends Controller
{
    /**
     * Core modules (only these can be the root module for a user).
     */
    protected $coreModules = ['HRM', 'MAN', 'LOG'];

    /**
     * Feature modules (these can be assigned to elevated users).
     */
    protected $featureModules = ['ECO', 'ORD', 'SCM', 'WAR', 'INV', 'PRO', 'WRF'];

    /**
     * Get the root module for a given user.
     * For manufacturing supervisors, root is always 'MAN'.
     * For others, derive from role.
     */
    protected function getRootModuleForUser(User $user): ?string
    {
        // Manufacturing supervisors always have MAN as root
        if ($user->is_manufacturing_supervisor) {
            return 'MAN';
        }
        // For regular managers/secretaries/GMs, derive from role
        if (!$user->role) return null;
        $roleUpper = strtoupper($user->role);
        foreach ($this->coreModules as $core) {
            if (str_contains($roleUpper, $core)) {
                return $core;
            }
        }
        return null;
    }

    /**
     * Get the list of modules that can be assigned to an elevated user.
     * For core module managers (including manufacturing supervisors): feature modules + their own core module.
     */
    protected function getAssignableModulesForUser(User $user): array
    {
        $root = $this->getRootModuleForUser($user);
        if ($root && in_array($root, $this->coreModules)) {
            // Core manager or manufacturing supervisor: feature modules + own core module
            return array_merge([$root], $this->featureModules);
        }
        // Fallback: feature modules only
        return $this->featureModules;
    }

    public function index()
    {
        // Include users who are:
        // - managers (position = manager)
        // - secretaries / general managers
        // - manufacturing supervisors (regardless of position)
        // Exclude CEO
        $users = User::with('moduleAccess')
            ->where(function ($query) {
                $query->whereIn('position', ['manager', 'secretary', 'general_manager'])
                      ->orWhere('is_manufacturing_supervisor', true);
            })
            ->where('role', '!=', 'CEO')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $grantedModules = $user->moduleAccess->pluck('module')->toArray();
                $isElevated = $user->is_manufacturing_supervisor ||
                              in_array($user->position, ['secretary', 'general_manager']);
                $rootModule = $this->getRootModuleForUser($user);

                // For elevated users, ensure root module is always present
                if ($isElevated && $rootModule && !in_array($rootModule, $grantedModules)) {
                    $grantedModules[] = $rootModule;
                }

                // Determine which modules can be assigned to this user
                $assignableModules = $isElevated ? $this->getAssignableModulesForUser($user) : [];

                // For display, we keep the original position, but add a 'display_position' for UI
                $displayPosition = $user->position;
                if ($user->is_manufacturing_supervisor && $user->position === 'staff') {
                    $displayPosition = 'manufacturing_supervisor';
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'position' => $user->position,
                    'display_position' => $displayPosition,
                    'is_manufacturing_supervisor' => $user->is_manufacturing_supervisor,
                    'granted_modules' => $grantedModules,
                    'root_module' => $rootModule,
                    'assignable_modules' => $assignableModules,
                ];
            });

        $allModules = collect($this->featureModules)->map(fn($key) => [
            'key' => $key,
            'name' => $this->getModuleName($key),
        ])->toArray();

        return Inertia::render('Dashboard/CEO/Access', [
            'users' => $users,
            'allModules' => $allModules,
        ]);
    }

    public function updatePosition(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'position' => 'required|in:manager,secretary,general_manager',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->role === 'CEO') {
            return back()->withErrors(['error' => 'Cannot change CEO position.']);
        }

        // Manufacturing supervisors cannot have their position changed (they remain staff)
        if ($user->is_manufacturing_supervisor) {
            return back()->withErrors(['error' => 'Manufacturing supervisors cannot be promoted/demoted using this action.']);
        }

        $user->position = $request->position;
        $user->save();

        if ($request->position === 'manager') {
            $user->moduleAccess()->delete();
        }

        if (in_array($request->position, ['secretary', 'general_manager'])) {
            $rootModule = $this->getRootModuleForUser($user);
            if ($rootModule) {
                $exists = UserModuleAccess::where('user_id', $user->id)
                    ->where('module', $rootModule)
                    ->exists();
                if (!$exists) {
                    UserModuleAccess::create([
                        'user_id' => $user->id,
                        'module' => $rootModule,
                        'granted_by' => auth()->id(),
                    ]);
                }
            }
        }

        return back()->with('success', 'Position updated successfully.');
    }

    public function updateModules(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'modules' => 'array',
            'modules.*' => 'string',
        ]);

        $user = User::findOrFail($request->user_id);

        // Allow module assignment for:
        // - secretaries / general managers
        // - manufacturing supervisors
        $isEligible = in_array($user->position, ['secretary', 'general_manager']) || $user->is_manufacturing_supervisor;
        if (!$isEligible) {
            return back()->withErrors(['error' => 'Module access only applicable for Secretaries, General Managers, or Manufacturing Supervisors.']);
        }

        $rootModule = $this->getRootModuleForUser($user);
        $allowedModules = $this->getAssignableModulesForUser($user);

        $modules = $request->modules ?? [];

        // Ensure root module is always present
        if ($rootModule && !in_array($rootModule, $modules)) {
            $modules[] = $rootModule;
        }

        // Filter out any module that is not allowed
        $modules = array_intersect($modules, $allowedModules);
        $modules = array_unique($modules);

        // Clear existing and recreate
        $user->moduleAccess()->delete();
        foreach ($modules as $module) {
            UserModuleAccess::create([
                'user_id' => $user->id,
                'module' => $module,
                'granted_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Module permissions updated.');
    }

    private function getModuleName(string $key): string
    {
        $names = [
            'HRM' => 'Human Resource',
            'MAN' => 'Manufacturing',
            'LOG' => 'Logistics',
            'ECO' => 'E-Commerce',
            'ORD' => 'Order Management',
            'SCM' => 'Supply Chain',
            'WAR' => 'Warehouse',
            'INV' => 'Inventory',
            'PRO' => 'Procurement',
            'WRF' => 'Workforce Management',
        ];
        return $names[$key] ?? $key;
    }
}