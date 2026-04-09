<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\eco\Inquiry;
use App\Models\eco\ConversationMessage;
use App\Models\eco\ConversationAttachment;
use App\Models\EcoQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ClientConversationController extends Controller
{
    /**
     * Display a listing of inquiries for the client.
     */
    public function index()
    {
        $inquiries = Inquiry::where('client_id', Auth::guard('client')->id())
            ->with('product')
            ->latest()
            ->get();

        return Inertia::render('Client/ConversationList', ['inquiries' => $inquiries]);
    }

    /**
     * Show the specific conversation and its quotations.
     */
    public function show(Inquiry $inquiry)
    {
        $this->authorizeClient($inquiry);

        // Load messages (with their attachments) and product info
        $inquiry->load(['messages' => function ($query) {
            $query->with('attachments')->oldest();
        }, 'product']);

        // Fetch ECO Quotations with fabric items
        $quotations = EcoQuotation::with('items')
            ->where('inquiry_id', $inquiry->id)
            ->latest()
            ->get();

        return Inertia::render('Client/ConversationShow', [
            'inquiry' => $inquiry,
            'quotations' => $quotations,
        ]);
    }

    /**
     * Handle sending a message with multiple file support.
     */
    public function sendMessage(Request $request, Inquiry $inquiry)
    {
        $this->authorizeClient($inquiry);

        $request->validate([
            'message' => 'required_without:files|nullable|string',
            'files.*' => 'nullable|file|max:10240', // 10MB limit per file
        ]);

        // 1. Create the Message
        $message = ConversationMessage::create([
            'inquiry_id' => $inquiry->id,
            'sender_type' => 'client',
            'message' => $request->message ?? '',
        ]);

        // 2. Handle Multiple Files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('eco_attachments', 'public');

                ConversationAttachment::create([
                    'conversation_message_id' => $message->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                ]);
            }
        }

        $inquiry->update(['last_message_at' => now()]);

        return back()->with('success', 'Message sent.');
    }

    /**
     * Handle quotation acceptance.
     */
    public function acceptQuotation(EcoQuotation $quotation)
    {
        if ($quotation->client_id !== Auth::guard('client')->id() || $quotation->status !== 'sent') {
            abort(403);
        }

        $quotation->update(['status' => 'accepted']);
        $quotation->inquiry->update(['status' => 'converted']);

        ConversationMessage::create([
            'inquiry_id' => $quotation->inquiry_id,
            'sender_type' => 'client',
            'message' => "Quotation {$quotation->quotation_number} has been ACCEPTED by the client.",
            'is_system_event' => true,
        ]);

        return back()->with('success', 'Quotation accepted! Processing your order.');
    }

    /**
     * Handle quotation rejection.
     */
    public function rejectQuotation(Request $request, EcoQuotation $quotation)
    {
        if ($quotation->client_id !== Auth::guard('client')->id() || $quotation->status !== 'sent') {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
            'request_new' => 'boolean'
        ]);

        // 1. Update Status and Request flag
        $quotation->update([
            'status' => 'rejected',
            'reject_reason' => $request->reason,
            'request_new_quote' => $request->request_new // Ensure this column exists in your migration
        ]);

        // 2. Add System Message for visibility in chat
        $msg = "Quotation {$quotation->quotation_number} REJECTED. Reason: {$request->reason}";
        if ($request->request_new) {
            $msg .= " | Client requested a revised quotation.";
        }

        ConversationMessage::create([
            'inquiry_id' => $quotation->inquiry_id,
            'sender_type' => 'client',
            'message' => $msg,
            'is_system_event' => true,
        ]);

        return back()->with('success', 'Quotation rejected.');
    }

    /**
     * Delete an attachment and its physical file.
     */
    public function destroyAttachment(ConversationAttachment $attachment)
    {
        // Security check: attachment -> message -> inquiry -> client
        if ($attachment->message->inquiry->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'File removed.');
    }

    /**
     * Internal authorization check.
     */
    private function authorizeClient(Inquiry $inquiry)
    {
        if ($inquiry->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }
    }
}
