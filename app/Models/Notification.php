<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;
    protected $table = 'notification';
    protected $fillable = [
        'user_id',
        'sender_id',
        'content',
        'type',
        'read',
        'created_at',
        'updated_at',
    ];

    // Thông báo thuộc về User nào (người nhận)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Thông báo được gửi bởi User nào (người gửi)
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
