<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\VendorRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupplierAuthController extends Controller
{
    /** Show the supplier login form */
    public function showLogin()
    {
        return Inertia::render('Auth/SupplierLogin');
    }

    /** Handle supplier login */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('supplier')->attempt($credentials, $request->boolean('remember'))) {
            $supplier = Auth::guard('supplier')->user();

            // Check if the vendor registration is approved
            $registration = VendorRegistration::where('supplier_id', $supplier->id)
                            ->where('status', 'approved')
                            ->first();

            if (!$registration) {
                // Not approved or registration missing – log them out immediately
                Auth::guard('supplier')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Your vendor registration is still pending SCM approval or has been rejected.',
                ])->onlyInput('email');
            }

            // Approved – let them in
            $request->session()->regenerate();
            return redirect()->route('supplier.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /** Show the supplier registration form */
    public function create()
    {
        return Inertia::render('Auth/SupplierRegister');
    }

    /** Handle supplier registration */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'representative_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'email' => ['required', 'email', 'unique:suppliers,email'],
            'phone_number' => ['required', 'string', 'max:50'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        // Check if a vendor registration already exists for this email
        $existingRegistration = VendorRegistration::where('email', $validated['email'])->first();
        if ($existingRegistration) {
            return back()->withErrors([
                'email' => 'A registration with this email already exists. Please contact SCM for assistance.',
            ])->onlyInput('email');
        }

        // Use a database transaction to ensure both records are created or none
        DB::beginTransaction();

        try {
            // 1. Create the Supplier account
            $supplier = Supplier::create([
                'business_name' => $validated['business_name'],
                'representative_name' => $validated['representative_name'],
                'address' => $validated['address'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'password' => bcrypt($validated['password']),
            ]);

            // 2. Create the Vendor Registration ticket (status = pending)
            VendorRegistration::create([
                'supplier_id' => $supplier->id,
                'business_name' => $supplier->business_name,
                'representative_name' => $supplier->representative_name,
                'email' => $supplier->email,
                'phone_number' => $supplier->phone_number,
                'address' => $supplier->address,
                'status' => 'pending',
            ]);

            DB::commit();

            return redirect()->route('supplier.login')->with('status', 'Registration successful! Please wait for SCM approval before logging in.');
        } catch (\Exception $e) {
            DB::rollBack();

            // If the supplier was created but VendorRegistration failed (e.g., duplicate email), delete the supplier
            if (isset($supplier)) {
                $supplier->delete();
            }

            return back()->withErrors([
                'email' => 'Registration failed. Please try again or contact support.',
            ])->withInput();
        }
    }

    /** Log out the supplier */
    public function logout(Request $request)
    {
        Auth::guard('supplier')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}