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

    // Comment thuộc về Post nào
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // Comment thuộc về User nào
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Reply comment (cha) của comment này
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comments::class, 'reply_comment_id');
    }

    // Các reply (con) của comment này
    public function replies(): HasMany
    {
        return $this->hasMany(Comments::class, 'reply_comment_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($comment) {
            // Xóa tất cả replies của comment này (đệ quy)
            foreach ($comment->replies as $reply) {
                $reply->delete();
            }
        });
    }
}
