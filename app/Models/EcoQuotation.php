<?php

namespace App\Models;

use App\Models\eco\Inquiry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcoQuotation extends Model
{
    use HasFactory;

    // Specifies exactly which table to use
    protected $table = 'eco_quotations';

    protected $fillable = [
        'client_id',
        'inquiry_id',
        'quotation_number',
        'request_new_quote',
        'reject_reason',
        // 'delivery_date',
        'payment_terms',
        'notes',
        'grand_total',
        'status',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'request_new_quote' => 'boolean',
    ];

    /**
     * Relationship: One Quotation has many fabric items.
     */
    public function items()
    {
        return $this->hasMany(EcoQuotationItem::class, 'eco_quotation_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }
}
