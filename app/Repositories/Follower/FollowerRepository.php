<?php

namespace App\Repositories\Follower;

use App\Repositories\Base\BaseRepository;

interface FollowerRepository extends BaseRepository
{
    public function follow(int $followerId, int $followedId): mixed;
    public function acceptFollow(int $userId, int $followerId): mixed;
    public function declineFollow(int $userId, int $followerId): mixed;
    public function unfollow(int $followerId, int $followedId): mixed;
    public function getPendingFollowRequests(int $userId): mixed;
    public function checkExistingFollow(int $followerId, int $followedId): mixed;
    public function createNotification(int $senderId, int $receiverId, string $content, string $type): mixed;
}
