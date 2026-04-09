<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CrmPagePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccessController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Allow CRM managers OR CEO
        if (!in_array($user->role, ['CRM', 'CEO']) || ($user->role === 'CRM' && $user->position !== 'manager')) {
            abort(403, 'Only CRM managers or CEO can manage access.');
        }

        // Get all CRM users (both managers and staff) – CEO excluded from list
        $users = User::where('role', 'CRM')
                     ->with('crmPagePermissions')
                     ->get()
                     ->map(fn ($u) => [
                         'id'          => $u->id,
                         'name'        => $u->name,
                         'role'        => $u->role,
                         'position'    => $u->position,
                         'permissions' => $u->crmPagePermissions->pluck('page'),
                     ]);

        $pages = ['dashboard', 'lead', 'approval', 'customerprofile', 'investigation', 'interview', 'trainee', 'access'];

        return Inertia::render('Dashboard/CRM/Access', [
            'users' => $users,
            'pages' => $pages
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Same permission check for update
        if (!in_array($user->role, ['CRM', 'CEO']) || ($user->role === 'CRM' && $user->position !== 'manager')) {
            abort(403, 'Only CRM managers or CEO can manage access.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pages'   => 'required|array',
            'pages.*' => 'string',
        ]);

        // Ensure the target user is a CRM user
        $targetUser = User::findOrFail($request->user_id);
        if ($targetUser->role !== 'CRM') {
            return back()->with('error', 'Can only assign permissions to CRM users.');
        }

        CrmPagePermission::where('user_id', $request->user_id)->delete();
        foreach ($request->pages as $page) {
            CrmPagePermission::create([
                'user_id' => $request->user_id,
                'page'    => $page
            ]);
        }

        return back()->with('message', 'Permissions updated successfully.');
    }
}