<?php

namespace App\Services\Follower;

use App\Services\Base\BaseService;

interface FollowerService extends BaseService
{
    public function follow(int $followerId, int $followedId): mixed;
    public function acceptFollow(int $userId, int $followerId): mixed;
    public function declineFollow(int $userId, int $followerId): mixed;
    public function unfollow(int $followerId, int $followedId): mixed;
    public function getPendingFollowRequests(int $userId): mixed;
}
