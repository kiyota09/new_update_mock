<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\eco\Inquiry;
use App\Models\eco\ConversationMessage;
use App\Models\eco\ConversationAttachment;
use App\Models\EcoQuotation;
use App\Models\SalesOrder; // Updated to match your ECO namespace
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ClientConversationController extends Controller
{
    public function index()
    {
        $inquiries = Inquiry::where('client_id', Auth::guard('client')->id())
            ->with('product')
            ->latest()
            ->get();

        return Inertia::render('Client/ConversationList', ['inquiries' => $inquiries]);
    }

    public function show(Inquiry $inquiry)
    {
        $this->authorizeClient($inquiry);

        $inquiry->load(['messages' => function ($query) {
            $query->with('attachments')->oldest();
        }, 'product']);

        $quotations = EcoQuotation::with('items')
            ->where('inquiry_id', $inquiry->id)
            ->latest()
            ->get();

        return Inertia::render('Client/ConversationShow', [
            'inquiry' => $inquiry,
            'quotations' => $quotations,
        ]);
    }

    public function sendMessage(Request $request, Inquiry $inquiry)
    {
        $this->authorizeClient($inquiry);

        $request->validate([
            'message' => 'required_without:files|nullable|string',
            'files.*' => 'nullable|file|max:10240', // 10MB limit per file
        ]);

        $message = ConversationMessage::create([
            'inquiry_id' => $inquiry->id,
            'sender_type' => 'client',
            'message' => $request->message ?? '',
        ]);

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

    public function acceptQuotation(Request $request, EcoQuotation $quotation)
    {
        // Authorization (Commented out as per your snippet)
        // if ($quotation->client_id !== Auth::guard('client')->id() || $quotation->status !== 'sent') {
        //     abort(403);
        // }

        // 1. Generate PO Control Number (Format: PO-YYYYMMDD-0001)
        $dateString = now()->format('Ymd'); // e.g., 20260416
        $dateForQuery = now()->format('Y-m-d');

        // Find the last sales order created today to get the next sequence number
        $lastOrder = SalesOrder::whereDate('created_at', $dateForQuery)
            ->orderBy('id', 'desc')
            ->first();

        // Regex updated to look for PO-8digits-digits
        if ($lastOrder && preg_match('/PO-\d{8}-(\d+)/', $lastOrder->purchase_order_id, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }

        $poControlNumber = 'PO-' . $dateString . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

        // 2. Update Statuses
        $quotation->update(['status' => 'accepted']);
        $quotation->inquiry->update(['status' => 'converted']);

        // 3. Generate unique Job Order Number for this batch
        $joNumber = 'JO-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        // 4. Create Sales Orders for EACH color/item in the quotation
        foreach ($quotation->items as $index => $item) {
            // Generate a Control Number (e.g., CTL-26RED-UNIQUEID)
            $colorCode = strtoupper(substr($item->color ?? 'COL', 0, 3));
            $controlNumber = 'CTL-' . now()->format('y') . $colorCode . '-' . strtoupper(Str::random(5));

            SalesOrder::create([
                'purchase_order_id' => $poControlNumber, // Format: PO-20260416-0001
                'jo_number'         => $joNumber,
                'control_number'    => $controlNumber,
                'color'             => $item->color,
                'quantity'          => $item->kilos,
                'yarn_type'         => $item->fabric,
                'design'            => $item->design ?? 'Standard',
                'status'            => 'pending',
                'client_id'         => $quotation->client_id,
            ]);
        }

        // 5. Create a system message in the chat history
        $messageText = "Quotation {$quotation->quotation_number} has been ACCEPTED by the client.\nPO Number: {$poControlNumber}\nJO Number: {$joNumber}";
        if ($request->notes) {
            $messageText .= "\n\nNotes from client: " . $request->notes;
        }

        $message = ConversationMessage::create([
            'inquiry_id'      => $quotation->inquiry_id,
            'sender_type'     => 'client',
            'message'         => $messageText,
            'is_system_event' => true,
        ]);

        // 6. Handle the files uploaded in the Modal
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('eco_attachments', 'public');
                ConversationAttachment::create([
                    'conversation_message_id' => $message->id,
                    'file_path'               => $path,
                    'file_name'               => $file->getClientOriginalName(),
                    'file_type'               => $file->getMimeType(),
                ]);
            }
        }

        return back()->with('success', 'Quotation accepted. PO ' . $poControlNumber . ' and Job Orders generated.');
    }

    public function rejectQuotation(Request $request, EcoQuotation $quotation)
    {
        // if ($quotation->client_id !== Auth::guard('client')->id() || $quotation->status !== 'sent') {
        //     abort(403);
        // }

        $request->validate([
            'reason' => 'required|string|max:1000',
            'request_new' => 'boolean'
        ]);

        $quotation->update([
            'status' => 'rejected',
            'reject_reason' => $request->reason,
            'request_new_quote' => $request->request_new
        ]);

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

    public function destroyAttachment(ConversationAttachment $attachment)
    {
        if ($attachment->message->inquiry->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
        return back()->with('success', 'File removed.');
    }

    private function authorizeClient(Inquiry $inquiry)
    {
        if ($inquiry->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }
    }
}
