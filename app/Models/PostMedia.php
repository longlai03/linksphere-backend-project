<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PostMedia extends Model
{
    use HasFactory;
    protected $table = 'post_media';

    protected $fillable = [
        'post_id',
        'user_id',
        'position',
        'tagged_user',
        'uploaded_at',
    ];
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function attachment(): HasOne
    {
        return $this->hasOne(Attachment::class, 'post_media_id');
    }
}
