<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\Post;
use App\Repositories\Base\BaseRepositoryElq;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

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

    public function getUserById(int $userId): mixed
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        $followersCount = DB::table('followers')
            ->where('followed_id', $userId)
            ->where('status', 'accepted')
            ->count();
        $followingCount = DB::table('followers')
            ->where('follower_id', $userId)
            ->where('status', 'accepted')
            ->count();
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
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%");
            });
        } else {
            $query->limit(20);
        }
        return $query->select([
            'id',
            'username',
            'nickname',
            'avatar_url',
            'bio',
            'created_at'
        ])->get();
    }

    public function getPublicProfile(int $userId): mixed
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        $followersCount = DB::table('followers')
            ->where('followed_id', $userId)
            ->where('status', 'accepted')
            ->count();
        $followingCount = DB::table('followers')
            ->where('follower_id', $userId)
            ->where('status', 'accepted')
            ->count();
        $postsCount = Post::where('user_id', $userId)->count();
        return [
            'user' => $user,
            'stats' => [
                'followers_count' => $followersCount,
                'following_count' => $followingCount,
                'posts_count' => $postsCount
            ],
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
        $privacy = [];
        if ($currentUserId == $userId) {
            $privacy = null;
        } else {
            $isFollower = DB::table('followers')
                ->where('follower_id', $currentUserId)
                ->where('followed_id', $userId)
                ->where('status', 'accepted')
                ->exists();
            if ($isFollower) {
                $privacy = ['public', 'friends'];
            } else {
                $privacy = ['public'];
            }
        }
        $query = Post::where('user_id', $userId)
            ->with('user', 'media.attachment');
        if ($privacy !== null) {
            $query->whereIn('privacy', $privacy);
        }
        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getSuggestionUser(int $currentUserId): mixed
    {
        try {
            $followingUserIds = DB::table('followers')
                ->where('follower_id', $currentUserId)
                ->pluck('followed_id')
                ->toArray();
            $excludeUserIds = array_merge([$currentUserId], $followingUserIds);
            $suggestions = User::whereNotIn('id', $excludeUserIds)->select([
                'id',
                'username',
                'nickname',
                'avatar_url',
                'bio',
                'created_at'
            ])->limit(10)->get();
            return $suggestions;
        } catch (Exception $e) {
            logger()->error('Error getSuggestionUser: ' . $e->getMessage());
            return false;
        }
    }
}
