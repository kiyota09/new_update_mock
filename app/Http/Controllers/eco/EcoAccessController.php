<?php

namespace App\Http\Controllers\eco;

use App\Http\Controllers\Controller;
use App\Models\eco\EcoAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EcoAccessController extends Controller
{
    public function index()
    {
        // Only CEO can see this page (middleware will enforce)
        $users = User::whereIn('role', ['CEO', 'Secretary', 'General Manager'])->get();
        $accesses = EcoAccess::pluck('can_access_eco', 'user_id')->toArray();
        return Inertia::render('Dashboard/ECO/Access', [
            'users' => $users,
            'accesses' => $accesses,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_access' => 'required|boolean',
        ]);
        EcoAccess::updateOrCreate(
            ['user_id' => $request->user_id],
            ['can_access_eco' => $request->can_access]
        );
        return back()->with('success', 'Access updated.');
    }
}