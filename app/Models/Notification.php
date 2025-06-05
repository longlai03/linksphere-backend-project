<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'type',
        'read',
        'created_at',
        'updated_at',
    ];

    // Thông báo thuộc về User nào
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
