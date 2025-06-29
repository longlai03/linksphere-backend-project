<?php

namespace App\Repositories\User;

use App\Repositories\Base\BaseRepository;

interface UserRepository extends BaseRepository
{
    public function registerUser(array $attributes): mixed;
    public function loginUser(array $attributes): mixed;
    public function updateUser(int $id, array $attributes): mixed;
    public function deleteUser(int $id): mixed;
    public function updateVerifyUser(int $id, bool $verify): bool;

    // New methods for UserController
    public function getUserById(int $userId): mixed;
    public function getUsers(array $filters): mixed;
    public function getPublicProfile(int $userId): mixed;
    public function getFollowStatus(int $currentUserId, int $targetUserId): mixed;
    public function getFollowers(int $userId): mixed;
    public function getFollowing(int $userId): mixed;
    public function getAllPostsByUser(int $userId, int $currentUserId): mixed;
}
