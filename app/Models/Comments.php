<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comments extends Model
{
    use HasFactory;
    protected $table = 'comments';
    protected $fillable = [
        'reply_comment_id',
        'post_id',
        'user_id',
        'content',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Reply comment (cha) của comment
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comments::class, 'reply_comment_id');
    }

    // Các reply (con) của comment
    public function replies(): HasMany
    {
        return $this->hasMany(Comments::class, 'reply_comment_id');
    }

    // Xóa tất cả replies của comment này
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($comment) {
            foreach ($comment->replies as $reply) {
                $reply->delete();
            }
        });
    }
}
