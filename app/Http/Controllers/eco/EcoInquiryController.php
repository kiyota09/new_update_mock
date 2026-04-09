<?php

namespace App\Http\Controllers\eco;

use App\Http\Controllers\Controller;
use App\Models\eco\Inquiry;
use App\Models\eco\ConversationMessage;
use App\Models\eco\ConversationAttachment;
use App\Models\EcoQuotation; 
use App\Models\EcoQuotationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EcoInquiryController extends Controller
{
    /**
     * Display a listing of the inquiries.
     */
    public function index()
    {
        $inquiries = Inquiry::with(['client', 'product'])->latest()->get();
        return Inertia::render('Dashboard/ECO/Inquiry', ['inquiries' => $inquiries]);
    }

    /**
     * Display the specific inquiry and conversation page.
     */
    public function show($id)
    {
        $inquiry = Inquiry::with(['client', 'product', 'messages' => function ($query) {
            $query->with('attachments')->oldest();
        }])->findOrFail($id);

        $quotations = EcoQuotation::with('items')
            ->where('inquiry_id', $inquiry->id)
            ->latest()
            ->get();

        return Inertia::render('Dashboard/ECO/InquiryShow', [
            'inquiry' => $inquiry,
            'quotations' => $quotations,
            'allProducts' => \App\Models\inv\Product::select('id', 'name')->get(),
        ]);
    }

    /**
     * Handle the "Issue Quotation" form submission.
     */
    public function issueQuotation(Request $request, Inquiry $inquiry)
    {
        // Validation updated based on your submitted data
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.fabric' => 'required|string',
            'items.*.design' => 'nullable|string',
            'items.*.color' => 'required|string',
            'items.*.kilos' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            // Made delivery_date nullable since it was missing in your dd()
            'delivery_date' => 'nullable|date', 
            'payment_terms' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Calculate total based on Price * Kilos
        $grandTotal = collect($validated['items'])->sum(function($item) {
            return (float)$item['price'] * (float)$item['kilos'];
        });

        DB::beginTransaction();
        try {
            $quotation = EcoQuotation::create([
                'client_id'        => $inquiry->client_id,
                'inquiry_id'       => $inquiry->id,
                'quotation_number' => 'ECO-QT-' . date('Y') . '-' . strtoupper(Str::random(5)),
                'delivery_date'    => $validated['delivery_date'] ?? now()->addDays(7), // Default if missing
                'payment_terms'    => $validated['payment_terms'],
                'notes'            => $validated['notes'],
                'grand_total'      => $grandTotal,
                'status'           => 'sent',
            ]);

            foreach ($validated['items'] as $item) {
                // Check if the fabric name matches the inquiry's requested product name
                $productId = (isset($inquiry->product) && $item['fabric'] === $inquiry->product->name) 
                             ? $inquiry->product_id 
                             : null;

                $quotation->items()->create([
                    'product_id' => $productId,
                    'fabric'     => $item['fabric'],
                    'design'     => $item['design'] ?? null,
                    'color'      => $item['color'],
                    'kilos'      => $item['kilos'],
                    'price'      => $item['price'],
                ]);
            }

            // Create System Message for the chat
            ConversationMessage::create([
                'inquiry_id'      => $inquiry->id,
                'sender_type'     => 'eco',
                'message'         => "Quotation Issued: {$quotation->quotation_number}\nTotal: ₱" . number_format($grandTotal, 2) . "\nTerms: " . $validated['payment_terms'],
                'is_system_event' => true,
            ]);

            // Update Inquiry Status
            $inquiry->update([
                'status' => 'quotation_sent',
                'last_message_at' => now()
            ]);

            DB::commit();
            return back()->with('success', 'Quotation sent successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quotation Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Database Error: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Handle sending a standard chat message with multiple files.
     */
    public function sendMessage(Request $request, Inquiry $inquiry)
    {
        $request->validate([
            'message' => 'required_without:files|nullable|string|max:2000',
            'files.*' => 'nullable|file|max:10240', // 10MB per file
        ]);

        $message = ConversationMessage::create([
            'inquiry_id' => $inquiry->id,
            'sender_type' => 'eco',
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

        return back();
    }
    /**
 * Reject the inquiry.
 */
public function reject(Request $request, Inquiry $inquiry)
{
    $request->validate([
        'reason' => 'nullable|string|max:500',
    ]);

    DB::beginTransaction();
    try {
        // 1. Update Inquiry Status
        $inquiry->update([
            'status' => 'rejected',
            'last_message_at' => now()
        ]);

        // 2. Add a System Message to the conversation
        $reasonText = $request->reason ? "\nReason: " . $request->reason : "";
        ConversationMessage::create([
            'inquiry_id' => $inquiry->id,
            'sender_type' => 'eco',
            'message' => "Inquiry Rejected by ECO Manager." . $reasonText,
            'is_system_event' => true,
        ]);

        DB::commit();
        return back()->with('success', 'Inquiry has been rejected.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Failed to reject: ' . $e->getMessage()]);
    }
}
}