<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'username',
        'nickname',
        'password',
        'email_verified_at',
        'avatar_url',
        'gender',
        'birthday',
        'address',
        'bio',
        'hobbies',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    // Quan hệ với bảng Post (1 user có nhiều bài đăng)
    public function post(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // Quan hệ với bảng Comment (1 user có nhiều bình luận)
    public function comments(): HasMany
    {
        return $this->hasMany(Comments::class);
    }

    // Quan hệ với bảng Reaction (1 user có nhiều reaction)
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    // Quan hệ với bảng PostMedia (1 user upload nhiều media)
    public function postMedia(): HasMany
    {
        return $this->hasMany(PostMedia::class);
    }

    // Quan hệ với bảng Attachment (1 user upload nhiều file đính kèm)
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    // Quan hệ với bảng Notification (1 user có nhiều thông báo)
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    // Quan hệ followers - những người theo dõi user này (followers)
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'followers',
            'followed_id',
            'follower_id'
        )->withPivot('status', 'request_at', 'respond_at');
    }

    // Quan hệ following - những người user này theo dõi (followings)
    public function followings(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'followers',
            'follower_id',
            'followed_id',
        )->withPivot('status', 'request_at', 'respond_at');
    }

    // Quan hệ Chat mà user tạo (1 user tạo nhiều chat)
    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'create_by');
    }

    // Quan hệ ChatMessage mà user gửi (1 user gửi nhiều tin nhắn)
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
