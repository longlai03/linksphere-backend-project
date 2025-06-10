<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;
    protected $table = 'chat_message';

    protected $fillable = [
        'chat_id',
        'attachment_id',
        'sender_id',
        'status',
        'content',
        'sent_at',
        'created_at',
        'updated_at',
    ];

    // Tin nhắn thuộc Chat nào
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    // Tin nhắn được gửi bởi User nào
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Tin nhắn có thể có 1 file đính kèm
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }
}
