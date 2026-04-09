<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeLoginController extends Controller
{
    /**
     * Display the employee login view.
     */
    public function create(): Response
    {
        // This renders the Login.vue page you updated with the toggle
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'employee_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // 1. Attempt to authenticate the user using the employee_id and password
        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            $user = Auth::user();

            // 2. Check if the user's position is 'trainee' based on the database enum
            if ($user->position === 'trainee') {
                // Log them out immediately to prevent session creation
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Throw an error back to the Employee ID field in Login.vue
                throw ValidationException::withMessages([
                    'employee_id' => __('Access Denied. Trainee accounts are not permitted to use this portal until promoted.'),
                ]);
            }

            // 3. If they are 'staff' or 'manager', regenerate session and redirect
            $request->session()->regenerate();

            // Redirect to the Dashboard/USERS/app.vue route defined in your web.php
            return redirect()->intended(route('employee.ui.dashboard'));
        }

        // Standard error for incorrect credentials
        return back()->withErrors([
            'employee_id' => __('The provided employee ID does not match our records.'),
        ]);
    }
}
