<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    use HasFactory;
    protected $table = 'reaction';

    public $timestamps = false;
    
    // Sử dụng auto-incrementing ID
    public $incrementing = true;

    protected $fillable = [
        'user_id',
        'post_id',
    ];

    // Reaction thuộc về User nào
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Reaction thuộc về Post nào
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
