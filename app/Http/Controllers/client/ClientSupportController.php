<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\crm\CrmFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientSupportController extends Controller
{
    public function index()
    {
        return Inertia::render('Client/Support');
    }

    public function storeComplaint(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);
        CrmFeedback::create([
            'client_id' => Auth::guard('client')->id(),
            'type' => 'complaint',
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'open',
        ]);
        return back()->with('success', 'Your feedback has been sent to CRM.');
    }
}