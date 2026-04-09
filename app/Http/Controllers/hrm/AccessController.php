<?php

namespace App\Http\Controllers\hrm;

use App\Http\Controllers\Controller;
use App\Models\PagePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccessController extends Controller
{
    public function index(Request $request)
    {
        // 1. DYNAMIC MODULE DETECTION
        // Extracts the prefix from the route name (e.g., 'scm.access.index' -> 'SCM')
        // This ensures the CEO can view and edit staff permissions for ANY department they visit.
        $routeName = $request->route()->getName();
        $modulePrefix = explode('.', $routeName)[0];
        $module = strtoupper($modulePrefix);

        $staff = User::where('role', $module)
            ->where('position', 'staff')
            ->with('pagePermissions')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                // Ensure we only grab permissions for the current module
                'permissions' => $u->pagePermissions->where('module', $module)->pluck('page')->values(),
            ]);

        $pages = $this->getPagesForModule($module);

        return Inertia::render('Dashboard/HRM/Access', [
            'staff' => $staff,
            'pages' => $pages,
            'currentModule' => $module,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pages' => 'required|array',
            'pages.*' => 'string',
        ]);

        // 2. DYNAMIC MODULE DETECTION (For Update)
        $routeName = $request->route()->getName();
        $modulePrefix = explode('.', $routeName)[0];
        $module = strtoupper($modulePrefix);

        // Remove existing permissions for this specific module and user
        PagePermission::where('user_id', $request->user_id)
            ->where('module', $module)
            ->delete();

        // Add new permissions
        foreach ($request->pages as $page) {
            PagePermission::create([
                'user_id' => $request->user_id,
                'module' => $module,
                'page' => $page,
            ]);
        }

        return back()->with('message', 'Permissions updated successfully.');
    }

    private function getPagesForModule($module)
    {
        // 3. COMPLETE MENU REGISTRY
        // Defines the exact menu IDs for all modules so the UI can build the checkboxes
        $pages = [
            'HRM' => ['dashboard', 'employee', 'application', 'interview', 'trainee', 'onboarding', 'reject', 'payroll', 'analytics', 'access'],
            'SCM' => ['dashboard', 'operations', 'sales', 'vendor', 'payments', 'assignment', 'close', 'interview', 'trainee', 'access'],
            'MAN' => ['dashboard', 'production', 'reject', 'interview', 'trainee', 'access'],
            'INV' => ['dashboard', 'inventory', 'planning', 'material', 'product', 'interview', 'trainee', 'access'],
            'PRO' => ['dashboard', 'quotation', 'request', 'receipt', 'interview', 'trainee', 'access'],
            'CRM' => ['dashboard', 'lead', 'profile', 'approval', 'oversight', 'strategy', 'interview', 'trainee', 'access'],
            'ECO' => ['dashboard', 'store', 'orders', 'quotations', 'credit', 'book', 'interview', 'trainee', 'access'],
            'FIN' => ['dashboard', 'interview', 'trainee', 'access'],
            'PROJ' => ['dashboard', 'interview', 'trainee', 'access'],
            'IT' => ['dashboard', 'interview', 'trainee', 'access'],
            'WAR' => ['dashboard', 'interview', 'trainee', 'access'],
            'ORD' => ['dashboard', 'interview', 'trainee', 'access'],
        ];

        // Fallback to basic dashboard and access if module is not explicitly detailed above
        return $pages[$module] ?? ['dashboard', 'interview', 'trainee', 'access'];
    }
}
