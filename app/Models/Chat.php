<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;
    protected $table = 'chat';
    protected $fillable = [
        'create_by',
        'participant_id',
        'created_at',
        'updated_at',
    ];
    // Chat do User nào tạo
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by');
    }
    // Các tin nhắn thuộc Chat này
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
    // Người còn lại trong hội thoại
    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_id');
    }
}
