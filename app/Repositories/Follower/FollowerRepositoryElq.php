<?php

namespace App\Repositories\Follower;

use App\Models\User;
use App\Repositories\Base\BaseRepositoryElq;
use App\Services\Notification\NotificationService;
use Exception;

class FollowerRepositoryElq extends BaseRepositoryElq implements FollowerRepository
{
    public function getModel(): string
    {
        return User::class;
    }

    public function follow(int $followerId, int $followedId): mixed
    {
        try {
            $follower = User::find($followerId);
            $followed = User::find($followedId);
            if (!$follower || !$followed) {
                return false;
            }
            $follower->followings()->attach($followedId, [
                'status' => 'pending',
                'request_at' => now()
            ]);
            $this->createNotification(
                $followerId,
                $followedId,
                "đã gửi yêu cầu theo dõi bạn",
                'follow_request'
            );

            return true;
        } catch (Exception $e) {
            throw new Exception('Error following user: ' . $e->getMessage());
        }
    }

    public function acceptFollow(int $userId, int $followerId): mixed
    {
        try {
            $user = User::find($userId);
            $follower = User::find($followerId);
            if (!$user || !$follower) {
                return false;
            }
            $follow = $user->followers()
                ->where('follower_id', $followerId)
                ->wherePivot('status', 'pending')
                ->first();
            if (!$follow) {
                return false;
            }
            $user->followers()->updateExistingPivot($followerId, [
                'status' => 'accepted',
                'respond_at' => now()
            ]);
            $this->createNotification(
                $userId,
                $followerId,
                "đã chấp nhận yêu cầu theo dõi của bạn",
                'follow_accepted'
            );
            return true;
        } catch (Exception $e) {
            throw new Exception('Error accepting follow request: ' . $e->getMessage());
        }
    }

    public function declineFollow(int $userId, int $followerId): mixed
    {
        try {
            $user = User::find($userId);
            $follower = User::find($followerId);
            if (!$user || !$follower) {
                return false;
            }
            $follow = $user->followers()
                ->where('follower_id', $followerId)
                ->wherePivot('status', 'pending')
                ->first();
            if (!$follow) {
                return false;
            }
            $user->followers()->updateExistingPivot($followerId, [
                'status' => 'declined',
                'respond_at' => now()
            ]);
            return true;
        } catch (Exception $e) {
            throw new Exception('Error declining follow request: ' . $e->getMessage());
        }
    }

    public function unfollow(int $followerId, int $followedId): mixed
    {
        try {
            $follower = User::find($followerId);
            $followed = User::find($followedId);

            if (!$follower || !$followed) {
                return false;
            }
            $detached = $follower->followings()->detach($followedId);
            return $detached > 0;
        } catch (Exception $e) {
            throw new Exception('Error unfollowing user: ' . $e->getMessage());
        }
    }

    public function getPendingFollowRequests(int $userId): mixed
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return false;
            }
            return $user->followers()
                ->wherePivot('status', 'pending')
                ->get()
                ->map(function ($follower) {
                    return [
                        'id' => $follower->id,
                        'username' => $follower->username,
                        'nickname' => $follower->nickname,
                        'avatar_url' => $follower->avatar_url,
                        'request_at' => $follower->pivot->request_at
                    ];
                });
        } catch (Exception $e) {
            throw new Exception('Error getting pending follow requests: ' . $e->getMessage());
        }
    }

    public function checkExistingFollow(int $followerId, int $followedId): mixed
    {
        try {
            $follower = User::find($followerId);
            if (!$follower) {
                return false;
            }
            return $follower->followings()
                ->where('followed_id', $followedId)
                ->first();
        } catch (Exception $e) {
            throw new Exception('Error checking existing follow: ' . $e->getMessage());
        }
    }

    public function createNotification(int $senderId, int $receiverId, string $content, string $type): mixed
    {
        try {
            return app(NotificationService::class)->createNotification([
                'user_id' => $receiverId,
                'sender_id' => $senderId,
                'content' => $content,
                'type' => $type,
                'read' => false
            ]);
        } catch (Exception $e) {
            throw new Exception('Error creating notification: ' . $e->getMessage());
        }
    }
}
