<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\Post;
use App\Repositories\Base\BaseRepositoryElq;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepositoryElq extends BaseRepositoryElq implements UserRepository
{
    public function getModel(): string
    {
        return User::class;
    }

    public function registerUser(array $attributes): mixed
    {

        return $this->create([
            'email' => $attributes['email'],
            'password' => Hash::make($attributes['password']),
            'username' => $attributes['username'],
            'nickname' => $attributes['nickname'],
        ]);
    }

    public function loginUser(array $attributes): mixed
    {
        return User::query()->where('email', $attributes['email'])->first();
    }
    public function updateUser(int $id, array $attributes): mixed
    {
        return $this->update($id, $attributes);
    }

    public function deleteUser(int $id): mixed
    {
        return $this->delete($id);
    }

    public function updateVerifyUser(int $id, bool $verify): bool
    {
        $user = $this->find($id);
        if (!$user) return false;

        $user->email_verified_at = now();
        $user->save();

        info('Email verified for user:', ['id' => $id]);
        return true;
    }

    // New methods for UserController
    public function getUserById(int $userId): mixed
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // Lấy số lượng followers và following
        $followersCount = DB::table('followers')
            ->where('followed_id', $userId)
            ->where('status', 'accepted')
            ->count();

        $followingCount = DB::table('followers')
            ->where('follower_id', $userId)
            ->where('status', 'accepted')
            ->count();

        // Lấy số lượng posts
        $postsCount = Post::where('user_id', $userId)->count();

        return [
            'user' => $user,
            'stats' => [
                'followers_count' => $followersCount,
                'following_count' => $followingCount,
                'posts_count' => $postsCount
            ]
        ];
    }

    public function getUsers(array $filters): mixed
    {
        $query = User::query();

        // Tìm kiếm theo username hoặc nickname
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        return $query->select([
            'id', 'username', 'nickname', 'avatar_url', 'bio', 'created_at'
        ])->get();
    }

    public function getPublicProfile(int $userId): mixed
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // Lấy thống kê
        $followersCount = DB::table('followers')
            ->where('followed_id', $userId)
            ->where('status', 'accepted')
            ->count();

        $followingCount = DB::table('followers')
            ->where('follower_id', $userId)
            ->where('status', 'accepted')
            ->count();

        $postsCount = Post::where('user_id', $userId)->count();

        // Lấy 3 posts gần nhất để preview
        $recentPosts = Post::where('user_id', $userId)
            ->with(['media.attachment'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return [
            'user' => $user,
            'stats' => [
                'followers_count' => $followersCount,
                'following_count' => $followingCount,
                'posts_count' => $postsCount
            ],
            'recent_posts' => $recentPosts
        ];
    }

    public function getFollowStatus(int $currentUserId, int $targetUserId): mixed
    {
        if ($currentUserId === $targetUserId) {
            return 'self';
        }

        $following = DB::table('followers')
            ->where('follower_id', $currentUserId)
            ->where('followed_id', $targetUserId)
            ->first();

        return $following ? $following->status : 'not_following';
    }

    public function getFollowers(int $userId): mixed
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        return $user->followers()
            ->wherePivot('status', 'accepted')
            ->get()
            ->map(function ($follower) {
                return [
                    'id' => $follower->id,
                    'username' => $follower->username,
                    'nickname' => $follower->nickname,
                    'avatar_url' => $follower->avatar_url
                ];
            });
    }

    public function getFollowing(int $userId): mixed
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        return $user->followings()
            ->wherePivot('status', 'accepted')
            ->get()
            ->map(function ($followed) {
                return [
                    'id' => $followed->id,
                    'username' => $followed->username,
                    'nickname' => $followed->nickname,
                    'avatar_url' => $followed->avatar_url
                ];
            });
    }

    public function getAllPostsByUser(int $userId, int $currentUserId): mixed
    {
        $isOwner = $currentUserId == $userId;
        $isFollower = false;
        
        if (!$isOwner) {
            $isFollower = DB::table('followers')
                ->where('follower_id', $currentUserId)
                ->where('followed_id', $userId)
                ->where('status', 'accepted')
                ->exists();
        }

        $query = Post::where('user_id', $userId)
            ->with('user', 'media.attachment');

        if ($isOwner) {
            // Xem được tất cả
        } elseif ($isFollower) {
            $query->whereIn('privacy', ['public', 'friends']);
        } else {
            $query->where('privacy', 'public');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
