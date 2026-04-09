<?php

namespace App\Http\Controllers\logistics;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogAccessController extends Controller
{
    public function index()
    {
        $users = User::whereIn('role', ['CEO', 'LOG'])
            ->orWhere('position', 'general_manager')
            ->orWhere('position', 'secretary')
            ->get();

        $access = DB::table('logistics_access')->pluck('can_access_logistics', 'user_id')->toArray();

        return inertia('Dashboard/Logistics/LogAccess', [
            'users' => $users,
            'access' => $access,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_access' => 'required|boolean',
        ]);

        DB::table('logistics_access')->updateOrInsert(
            ['user_id' => $request->user_id],
            ['can_access_logistics' => $request->can_access, 'updated_at' => now()]
        );

        return back()->with('success', 'Access updated.');
    }
}