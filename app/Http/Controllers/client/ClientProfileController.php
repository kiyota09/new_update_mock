<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientProfileController extends Controller
{
    public function edit()
    {
        $client = Auth::guard('client')->user();
        return Inertia::render('Client/Profile', ['client' => $client]);
    }

    public function update(Request $request)
    {
        $client = Auth::guard('client')->user();
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'company_address' => 'required|string',
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        $client->update($validated);
        return back()->with('success', 'Profile updated.');
    }
}