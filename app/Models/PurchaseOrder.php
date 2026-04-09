<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    use HasFactory;

    /**
     * Workflow Statuses:
     * 'credit_review'            - Initial state for ECO Manager review
     * 'tier_assignment'          - State for HR Manager business tiering
     * 'pending_client_approval'  - Final quote sent to client
     * 'approved'                 - Finalized order
     */
    protected $fillable = [
        'client_id',
        'po_number',
        'subtotal',
        'discount_amount',
        'total_amount',
        'status',
        'tier_level',
        'notes',
        'delivery_date',
        'attachment_path', // Added for manual P.O. uploads
        'control_number',  // Added for the detailed Pending Push table
        'yarn',            // Added for the detailed Pending Push table
    ];

    /**
     * Relationship: The items within this purchase order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Relationship: The client who placed the order.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relationship: The queue entry for dispatching to SCM/Order Management.
     * Used by the manufacturing module to track production stages.
     */
    public function queue(): HasOne
    {
        return $this->hasOne(OrderQueue::class, 'purchase_order_id');
    }

    /**
     * Relationship Alias: kept for backward compatibility and clarity 
     * in different modules.
     */
    public function orderQueue(): HasOne
    {
        return $this->queue();
    }

    /**
     * Relationship: Link to Job Orders if applicable.
     */
    public function jobOrders(): HasMany
    {
        return $this->hasMany(JobOrder::class);
    }
}