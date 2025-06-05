<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'caption',
        'privacy',
    ];

    // Quan hệ ngược đến User (bài viết thuộc về user nào)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Quan hệ với Comment (1 bài viết có nhiều comment)
    public function comments(): HasMany
    {
        return $this->hasMany(Comments::class);
    }

    // Quan hệ với Reaction (1 bài viết có nhiều reaction)
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    // Quan hệ với PostMedia (1 bài viết có nhiều media)
    public function postMedia(): HasMany
    {
        return $this->hasMany(PostMedia::class);
    }
}
