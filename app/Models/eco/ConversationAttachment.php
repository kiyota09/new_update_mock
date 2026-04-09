<?php

namespace App\Models\eco;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ConversationAttachment extends Model
{
    protected $table = 'eco_conversation_attachments';
    protected $fillable = [
        'conversation_message_id', 
        'file_path', 
        'file_name', 
        'file_type'
    ];

    // This makes the 'url' available in your Vue props automatically
    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    public function message()
    {
        return $this->belongsTo(ConversationMessage::class, 'conversation_message_id');
    }
}