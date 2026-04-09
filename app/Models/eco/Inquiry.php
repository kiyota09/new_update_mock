<?php

namespace App\Models\eco;

use App\Models\Client;
use App\Models\inv\Product;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $table = 'inquiries';
    protected $fillable = ['client_id', 'product_id', 'initial_message', 'status', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function messages()
    {
        return $this->hasMany(ConversationMessage::class);
    }
}