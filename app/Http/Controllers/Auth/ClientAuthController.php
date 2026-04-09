<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class ClientAuthController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/ClientRegister');
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'business_type' => 'required|string',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:clients',
            'phone' => 'required|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_address' => 'required|string',
        ]);

        Client::create([
            'company_name' => $request->company_name,
            'business_type' => $request->business_type,
            'tin_number' => $request->tin_number,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'company_address' => $request->company_address,
            'status' => 'pending', // default pending
        ]);

        return redirect()->route('client.login')->with('message', 'Registration submitted. Please wait for admin approval.');
    }

    public function showLogin()
    {
        return Inertia::render('Auth/ClientLogin');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $client = Client::where('email', $credentials['email'])->first();

        if ($client && Hash::check($credentials['password'], $client->password)) {
            // ✅ Allow both 'approved' and 'active' as valid statuses
            if (! in_array($client->status, ['approved', 'active'])) {
                return back()->withErrors([
                    'email' => 'Your account does not exist.',
                ]);
            }

            Auth::guard('client')->login($client, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('client.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Invalid Credentials.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }
}
