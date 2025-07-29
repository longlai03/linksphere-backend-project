<?php

namespace App\Services\Follower;

use App\Repositories\Follower\FollowerRepository;
use App\Services\Base\BaseServiceImp;
use Exception;

class FollowerServiceImp extends BaseServiceImp implements FollowerService
{
    private FollowerRepository $followerRepository;

    public function __construct(FollowerRepository $followerRepository)
    {
        $this->followerRepository = $followerRepository;
    }

    public function follow(int $followerId, int $followedId): mixed
    {
        try {
            if ($followerId === $followedId) {
                return ['error' => 'Không thể theo dõi chính mình'];
            }
            $existingFollow = $this->followerRepository->checkExistingFollow($followerId, $followedId);
            if ($existingFollow) {
                $status = match ($existingFollow->pivot->status) {
                    'pending' => 'Yêu cầu theo dõi đang chờ xử lý',
                    'accepted' => 'Bạn đã theo dõi người dùng này',
                    'declined' => 'Yêu cầu theo dõi của bạn đã bị từ chối',
                    default => 'Unknown status'
                };
                return ['error' => $status];
            }
            $result = $this->followerRepository->follow($followerId, $followedId);
            if (!$result) {
                return ['error' => 'Error following user'];
            }
            return [
                'success' => true,
                'message' => 'Follow request sent successfully'
            ];
        } catch (Exception $e) {
            logger()->error('Error following user: ' . $e->getMessage());
            return ['error' => 'Error following user'];
        }
    }

    public function acceptFollow(int $userId, int $followerId): mixed
    {
        try {
            $result = $this->followerRepository->acceptFollow($userId, $followerId);
            if (!$result) {
                return ['error' => 'No pending follow request found'];
            }
            return [
                'success' => true,
                'message' => 'Follow request accepted successfully'
            ];
        } catch (Exception $e) {
            logger()->error('Error accepting follow request: ' . $e->getMessage());
            return ['error' => 'Error accepting follow request'];
        }
    }

    public function declineFollow(int $userId, int $followerId): mixed
    {
        try {
            $result = $this->followerRepository->declineFollow($userId, $followerId);
            if (!$result) {
                return ['error' => 'No pending follow request found'];
            }
            return ['success' => true, 'message' => 'Follow request declined successfully'];
        } catch (Exception $e) {
            logger()->error('Error declining follow request: ' . $e->getMessage());
            return ['error' => 'Error declining follow request'];
        }
    }

    public function unfollow(int $followerId, int $followedId): mixed
    {
        try {
            $result = $this->followerRepository->unfollow($followerId, $followedId);
            if (!$result) {
                return ['error' => 'No follow relationship found'];
            }
            return [
                'success' => true,
                'message' => 'Unfollowed successfully'
            ];
        } catch (Exception $e) {
            logger()->error('Error unfollowing user: ' . $e->getMessage());
            return ['error' => 'Error unfollowing user'];
        }
    }

    public function getPendingFollowRequests(int $userId): mixed
    {
        try {
            $pendingRequests = $this->followerRepository->getPendingFollowRequests($userId);
            if ($pendingRequests === false) {
                return ['error' => 'Error getting pending follow requests'];
            }
            return [
                'success' => true,
                'pending_requests' => $pendingRequests
            ];
        } catch (Exception $e) {
            logger()->error('Error getting pending follow requests: ' . $e->getMessage());
            return ['error' => 'Error getting pending follow requests'];
        }
    }
}
