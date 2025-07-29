<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;
    protected $table = 'attachment';
    protected $fillable = [
        'user_id',
        'post_media_id',
        'file_url',
        'file_type',
        'original_file_name',
        'size',
        'uploaded_at',
    ];
    // Quan hệ đến User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    // Quan hệ đến PostMedia
    public function postMedia(): BelongsTo
    {
        return $this->belongsTo(PostMedia::class);
    }
}
