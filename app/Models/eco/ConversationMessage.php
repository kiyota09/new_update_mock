<?php

namespace App\Models\eco;

use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model
{
    protected $table = 'conversation_messages';
    protected $fillable = [
        'inquiry_id',
        'sender_type',
        'message',
        'attachment',
        'reject_reason',      // <--- If this is missing, the database stays null
        'request_new_quote',  // <--- If this is missing, the boolean won't save
        'meeting_data',
        'is_system_event'
    ];

    protected $casts = [
        'meeting_data' => 'array',
        'is_system_event' => 'boolean',
    ];

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }
    public function attachments()
    {
        return $this->hasMany(ConversationAttachment::class, 'conversation_message_id');
    }
}
