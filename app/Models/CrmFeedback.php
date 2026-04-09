<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'type',
        'subject',
        'message',
        'status',
        'assigned_to',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // Relationship to the client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relationship to the assigned staff user (CRM staff)
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
